<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

test('landlord guard is configured with jwt driver', function () {
    $guard = config('auth.guards.landlord');

    expect($guard)->not->toBeNull();
    expect($guard['driver'])->toBe('jwt');
    expect($guard['provider'])->toBe('admins');
});

test('tenant guard is configured with jwt driver', function () {
    $guard = config('auth.guards.tenant');

    expect($guard)->not->toBeNull();
    expect($guard['driver'])->toBe('jwt');
    expect($guard['provider'])->toBe('company_users');
});

test('admins provider is configured correctly', function () {
    $provider = config('auth.providers.admins');

    expect($provider)->not->toBeNull();
    expect($provider['driver'])->toBe('eloquent');
    expect($provider['model'])->toBe('Modules\GlobalAdmin\Models\Admin');
});

test('company users provider is configured correctly', function () {
    $provider = config('auth.providers.company_users');

    expect($provider)->not->toBeNull();
    expect($provider['driver'])->toBe('eloquent');
    expect($provider['model'])->toBe('Modules\CompanyManagement\Models\CompanyUser');
});

test('landlord guard can be instantiated', function () {
    $guard = auth('landlord');
    expect($guard)->not->toBeNull();
});

test('tenant guard can be instantiated', function () {
    $guard = auth('tenant');
    expect($guard)->not->toBeNull();
});
