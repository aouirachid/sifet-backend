<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Modules\GlobalAdmin\Models\Admin;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

test('admin implements jwt subject', function () {
    $admin = new Admin;
    expect($admin)->toBeInstanceOf(JWTSubject::class);
});

test('admin uses landlord connection', function () {
    $classes = class_uses_recursive(Admin::class);
    expect($classes)->toContain(UsesLandlordConnection::class);
});

test('admin jwt identifier is key', function () {
    $admin = new Admin(['id' => 'test-id']);
    // Mocking getKey or just relying on attribute if key is set
    // Since HasUuids might interfere if not saved, we simulate behavior
    // But simplest is checking implementation calls getKey()

    // We can't easily mock the internal getKey without saving or partial mock.
    // But we can check the return value if we set the attribute.
    $admin->id = 'test-id';
    expect($admin->getJWTIdentifier())->toBe('test-id');
});

test('admin jwt custom claims are empty', function () {
    $admin = new Admin;
    expect($admin->getJWTCustomClaims())->toBeArray()->toBeEmpty();
});
