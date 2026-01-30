<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\ValidateJwtTenantMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Modules\GlobalAdmin\Models\Tenant;
use PHPOpenSourceSaver\JWTAuth\Payload;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('middleware allows request when no tenant is current', function () {
    $middleware = new ValidateJwtTenantMiddleware;
    $request = Request::create('/api/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['status' => 'passed']);
    });

    expect($response->getData()->status)->toBe('passed');
});

test('middleware allows request when tenant matches jwt claim', function () {
    $tenant = new Tenant(['id' => 'tenant-1']);
    app()->instance('currentTenant', $tenant);

    $payload = Mockery::mock(Payload::class);
    $payload->shouldReceive('get')->with('tenant_id')->andReturn('tenant-1');

    $guard = Mockery::mock(\PHPOpenSourceSaver\JWTAuth\JWTGuard::class);
    $guard->shouldReceive('payload')->andReturn($payload);

    Auth::shouldReceive('guard')->with('tenant')->andReturn($guard);

    $middleware = new ValidateJwtTenantMiddleware;
    $request = Request::create('/api/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['status' => 'passed']);
    });

    expect($response->getData()->status)->toBe('passed');
});

test('middleware aborts with 403 when tenant mismatch', function () {
    $tenant = new Tenant(['id' => 'tenant-1']);
    app()->instance('currentTenant', $tenant);

    $payload = Mockery::mock(Payload::class);
    $payload->shouldReceive('get')->with('tenant_id')->andReturn('tenant-2');

    $guard = Mockery::mock(\PHPOpenSourceSaver\JWTAuth\JWTGuard::class);
    $guard->shouldReceive('payload')->andReturn($payload);

    Auth::shouldReceive('guard')->with('tenant')->andReturn($guard);

    $middleware = new ValidateJwtTenantMiddleware;
    $request = Request::create('/api/test', 'GET');

    expect(fn () => $middleware->handle($request, function ($req) {
        return response()->json(['status' => 'passed']);
    }))->toThrow(HttpException::class, 'Token not valid for this tenant');
});

test('middleware allows request when no jwt payload is present but tenant exists', function () {
    $tenant = new Tenant(['id' => 'tenant-1']);
    app()->instance('currentTenant', $tenant);

    // Simulate no valid JWT present (throws exception)
    $guard = Mockery::mock(\PHPOpenSourceSaver\JWTAuth\JWTGuard::class);
    $guard->shouldReceive('payload')->andThrow(new \Exception('No token'));

    Auth::shouldReceive('guard')->with('tenant')->andReturn($guard);

    $middleware = new ValidateJwtTenantMiddleware;
    $request = Request::create('/api/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['status' => 'passed']);
    });

    expect($response->getData()->status)->toBe('passed');
});

test('middleware instance can be created', function () {
    $middleware = new ValidateJwtTenantMiddleware;
    expect($middleware)->toBeInstanceOf(ValidateJwtTenantMiddleware::class);
});
