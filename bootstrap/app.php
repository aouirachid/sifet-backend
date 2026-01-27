<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtAuthenticate::class,
            'jwt.landlord' => \App\Http\Middleware\JwtAuthenticate::class.':landlord',
            'jwt.tenant' => \App\Http\Middleware\JwtAuthenticate::class.':tenant',
            'jwt.user' => \App\Http\Middleware\JwtAuthenticate::class.':api',
        ]);

        $middleware
            ->group('tenant', [
                \App\Http\Middleware\TenancyByJwtToken::class,
                \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
                \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
