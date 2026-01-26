<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\CompanyManagement\Models\CompanyUser;
use Modules\GlobalAdmin\Models\Admin;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

test('landlord guard uses jwt driver and can generate token for admin', function () {
    // Config check
    expect(config('auth.guards.landlord.driver'))->toBe('jwt');
    expect(config('auth.guards.landlord.provider'))->toBe('admins');

    // Token generation simulation
    $admin = new Admin([
        'name' => 'Super Admin',
        'email' => 'admin@sifet.com',
        'password' => 'secret',
    ]);
    // Force ID as string to satisfy Lcobucci JWT requirement
    $admin->id = (string) Str::uuid();

    $token = JWTAuth::fromUser($admin);
    expect($token)->toBeString()->not->toBeEmpty();

    $payload = JWTAuth::setToken($token)->getPayload();
    expect((string) $payload->get('sub'))->toBe((string) $admin->id);
});

test('tenant guard uses jwt driver and can generate token for company user with tenant_id', function () {
    // Config check
    expect(config('auth.guards.tenant.driver'))->toBe('jwt');
    expect(config('auth.guards.tenant.provider'))->toBe('company_users');

    // Token generation simulation
    $tenantId = (string) Str::uuid();
    $user = new CompanyUser([
        'name' => 'Company Employee',
        'email' => 'user@client.com',
        'password' => 'secret',
    ]);
    // Force ID as string
    $user->id = (string) Str::uuid();
    $user->tenant_id = $tenantId;

    $token = JWTAuth::fromUser($user);
    expect($token)->toBeString()->not->toBeEmpty();

    $payload = JWTAuth::setToken($token)->getPayload();
    expect((string) $payload->get('sub'))->toBe((string) $user->id);
    expect($payload->get('tenant_id'))->toBe($tenantId);
});

test('jwt secret is configured in env', function () {
    expect(config('jwt.secret'))->not->toBeNull();
    expect(strlen((string) config('jwt.secret')))->toBeGreaterThan(32);
});
