<?php

declare(strict_types=1);

use App\Http\Middleware\TenancyByJwtToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\GlobalAdmin\Models\Domain;
use Modules\GlobalAdmin\Models\Tenant;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    // Forcer TOUT sur une seule connexion SQLITE nommée 'landlord'
    Config::set('database.default', 'landlord');
    Config::set('database.connections.landlord', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
    Config::set('multitenancy.landlord_database_connection_name', 'landlord');

    // Créer les tables sur 'landlord'
    Schema::connection('landlord')->create('tenants', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('database_name');
        $table->json('data')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::connection('landlord')->create('domains', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('tenant_id');
        $table->string('domain');
        $table->timestamps();
    });

    Tenant::forgetCurrent();
});

test('it resolves tenant from jwt token', function () {
    $tenant = Tenant::create([
        'id' => (string) Str::uuid(),
        'database_name' => 'db_jwt',
        'data' => [],
    ]);

    $payload = Mockery::mock(\PHPOpenSourceSaver\JWTAuth\Payload::class);
    $payload->shouldReceive('get')->with('tenant_id')->andReturn($tenant->id);

    JWTAuth::shouldReceive('getToken')->once()->andReturn('fake-token');
    JWTAuth::shouldReceive('setToken')->once()->andReturnSelf();
    JWTAuth::shouldReceive('getPayload')->once()->andReturn($payload);

    $request = Request::create('/', 'GET');
    $middleware = new TenancyByJwtToken;
    $middleware->handle($request, function ($req) {
        return response('next');
    });

    expect(Tenant::current()->id)->toBe($tenant->id);
});

test('it resolves tenant from domain if jwt is missing', function () {
    $tenant = Tenant::create([
        'id' => (string) Str::uuid(),
        'database_name' => 'db_domain',
        'data' => [],
    ]);

    Domain::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'domain' => 'client1.test',
    ]);

    JWTAuth::shouldReceive('getToken')->andReturn(null);
    JWTAuth::shouldReceive('setToken')->never();

    $request = Request::create('http://client1.test', 'GET');
    $middleware = new TenancyByJwtToken;
    $middleware->handle($request, function ($req) {
        return response('next');
    });

    expect(Tenant::current()->id)->toBe($tenant->id);
});
