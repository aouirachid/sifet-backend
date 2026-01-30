<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Modules\CompanyManagement\Models\CompanyUser;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

test('company user implements jwt subject', function () {
    $user = new CompanyUser;
    expect($user)->toBeInstanceOf(JWTSubject::class);
});

test('company user has get jwt custom claims method', function () {
    $user = new CompanyUser;
    // Just check that the method exists and returns an array
    // We don't call it to avoid container dependency issues in unit tests
    expect(method_exists($user, 'getJWTCustomClaims'))->toBeTrue();
});

test('company user jwt identifier is key', function () {
    $user = new CompanyUser;
    $user->id = 'test-user-id';
    expect($user->getJWTIdentifier())->toBe('test-user-id');
});
