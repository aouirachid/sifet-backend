<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\ValidateJwtTenantMiddleware;
use Illuminate\Http\Request;

test('middleware allows request when no tenant is current', function () {
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
