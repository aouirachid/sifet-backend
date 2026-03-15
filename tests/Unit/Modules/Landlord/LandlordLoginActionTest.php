<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Landlord\Actions\LandlordLoginAction;
use Modules\Landlord\Models\LandlordUser;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Skip when PostgreSQL driver is not available (evaluated at load time so DB is not touched)
$skipWhenNoPgsql = ! extension_loaded('pdo_pgsql');

// Landlord module migrations run on landlord connection (CI sets DB_CONNECTION=landlord)

test('successful token generation for valid credentials', function () {
    // Factory default password is "password" (hashed by model cast)
    LandlordUser::factory()->create([
        'email' => 'landlord@example.com',
    ]);

    $action = new LandlordLoginAction;
    $result = $action->execute([
        'email' => 'landlord@example.com',
        'password' => 'password',
    ]);

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['token', 'user']);
    expect($result['token'])->toBeString()->not->toBeEmpty();
    expect($result['user'])->not->toBeNull();
})->skip($skipWhenNoPgsql, 'Requires pdo_pgsql (PostgreSQL driver)');

test('failure for invalid password', function () {
    LandlordUser::factory()->create([
        'email' => 'landlord@example.com',
    ]);

    $action = new LandlordLoginAction;
    $result = $action->execute([
        'email' => 'landlord@example.com',
        'password' => 'wrong-password',
    ]);

    expect($result)->toBeNull();
})->skip($skipWhenNoPgsql, 'Requires pdo_pgsql (PostgreSQL driver)');

test('failure for non-existent user', function () {
    $action = new LandlordLoginAction;
    $result = $action->execute([
        'email' => 'nonexistent@example.com',
        'password' => 'any-password',
    ]);

    expect($result)->toBeNull();
})->skip($skipWhenNoPgsql, 'Requires pdo_pgsql (PostgreSQL driver)');
