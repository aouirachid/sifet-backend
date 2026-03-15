<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Landlord\Models\LandlordUser;

uses(RefreshDatabase::class);

// Skip when PostgreSQL driver is not available (evaluated at load time)
$skipWhenNoPgsql = ! extension_loaded('pdo_pgsql');

test('successful login returns token and user resource', function () {
    LandlordUser::factory()->create([
        'email' => 'landlord@example.com',
    ]);

    $response = $this->postJson('/api/v1/landlord/login', [
        'email' => 'landlord@example.com',
        'password' => 'password',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'token',
        'user' => [
            'data' => [
                'id',
                'full_name',
                'email',
            ],
        ],
    ]);
})->skip($skipWhenNoPgsql, 'Requires pdo_pgsql (PostgreSQL driver)');

test('login fails with invalid credentials and returns 401', function () {
    LandlordUser::factory()->create([
        'email' => 'landlord@example.com',
    ]);

    $response = $this->postJson('/api/v1/landlord/login', [
        'email' => 'landlord@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
})->skip($skipWhenNoPgsql, 'Requires pdo_pgsql (PostgreSQL driver)');

test('rate limiting throttles after 5 attempts', function () {
    LandlordUser::factory()->create(['email' => 'landlord@example.com']);

    // 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/landlord/login', [
            'email' => 'landlord@example.com',
            'password' => 'wrong',
        ]);
    }

    // 6th attempt should be throttled (429)
    $response = $this->postJson('/api/v1/landlord/login', [
        'email' => 'landlord@example.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(429);
})->skip($skipWhenNoPgsql, 'Requires pdo_pgsql (PostgreSQL driver)');

test('guard isolation non-existent user returns 401', function () {
    // No LandlordUser created; landlord guard only checks landlord_users table
    $response = $this->postJson('/api/v1/landlord/login', [
        'email' => 'someone@example.com',
        'password' => 'password',
    ]);

    $response->assertUnauthorized();
})->skip($skipWhenNoPgsql, 'Requires pdo_pgsql (PostgreSQL driver)');