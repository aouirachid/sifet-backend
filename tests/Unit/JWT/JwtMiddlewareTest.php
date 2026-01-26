<?php

declare(strict_types=1);

namespace Tests\Unit\JWT;

use App\Http\Middleware\TenancyByJwtToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Payload;
use Tests\TestCase;

class JwtMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Tenant class statically using an alias
        $this->tenantMock = Mockery::mock('alias:Modules\GlobalAdmin\Models\Tenant');

        // Mock the DomainTenantFinder to avoid database queries during fallback
        $finderMock = Mockery::mock('App\TenantFinder\DomainTenantFinder');
        $finderMock->shouldReceive('findForRequest')->andReturn(null);
        $this->app->instance('App\TenantFinder\DomainTenantFinder', $finderMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_middleware_activates_tenant_when_tenant_id_is_present_in_jwt(): void
    {
        $tenantId = 'test-tenant-123';
        $request = Request::create('/api/test', 'GET');

        $tenant = Mockery::mock('Modules\GlobalAdmin\Models\TenantInstance');
        $tenant->shouldReceive('makeCurrent')->once();

        $this->tenantMock->shouldReceive('find')->with($tenantId)->once()->andReturn($tenant);

        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->with('tenant_id')->once()->andReturn($tenantId);

        JWTAuth::shouldReceive('getToken')->once()->andReturn('test-token');
        JWTAuth::shouldReceive('setToken')->with('test-token')->once()->andReturnSelf();
        JWTAuth::shouldReceive('getPayload')->once()->andReturn($payload);

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_continues_when_tenant_id_not_found_in_jwt(): void
    {
        $request = Request::create('/api/test', 'GET');

        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->with('tenant_id')->once()->andReturn(null);

        JWTAuth::shouldReceive('getToken')->once()->andReturn('test-token');
        JWTAuth::shouldReceive('setToken')->with('test-token')->once()->andReturnSelf();
        JWTAuth::shouldReceive('getPayload')->once()->andReturn($payload);

        $this->tenantMock->shouldReceive('find')->never();

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_continues_when_tenant_not_found_in_database(): void
    {
        $tenantId = 'non-existent-tenant';
        $request = Request::create('/api/test', 'GET');

        $this->tenantMock->shouldReceive('find')->with($tenantId)->once()->andReturn(null);

        $payload = Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->with('tenant_id')->once()->andReturn($tenantId);

        JWTAuth::shouldReceive('getToken')->once()->andReturn('test-token');
        JWTAuth::shouldReceive('setToken')->with('test-token')->once()->andReturnSelf();
        JWTAuth::shouldReceive('getPayload')->once()->andReturn($payload);

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_continues_when_jwt_token_is_missing(): void
    {
        $request = Request::create('/api/test', 'GET');

        JWTAuth::shouldReceive('getToken')->once()->andReturn(null);

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_continues_when_jwt_token_is_invalid(): void
    {
        $request = Request::create('/api/test', 'GET');

        JWTAuth::shouldReceive('getToken')->once()->andThrow(new JWTException('Token could not be parsed'));

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_logs_warning_on_unexpected_error(): void
    {
        $request = Request::create('/api/test', 'GET');
        $unexpectedError = new \Exception('Unexpected error');

        JWTAuth::shouldReceive('getToken')->once()->andThrow($unexpectedError);

        Log::shouldReceive('warning')
            ->once()
            ->with('Erreur lors de la résolution du tenant via JWT', [
                'error' => 'Unexpected error',
            ]);

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('OK');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_handles_various_jwt_exceptions(): void
    {
        $jwtExceptions = [
            new TokenExpiredException,
            new TokenInvalidException,
            new TokenBlacklistedException,
        ];

        foreach ($jwtExceptions as $exception) {
            $request = Request::create('/api/test', 'GET');

            JWTAuth::shouldReceive('getToken')->once()->andThrow($exception);

            $middleware = new TenancyByJwtToken;
            $next = function ($request) {
                return new Response('OK');
            };

            $response = $middleware->handle($request, $next);

            $this->assertEquals('OK', $response->getContent());
        }
    }

    public function test_middleware_can_handle_multiple_requests(): void
    {
        $firstTenantId = 'tenant-1';
        $secondTenantId = 'tenant-2';

        $firstTenant = Mockery::mock('Modules\GlobalAdmin\Models\TenantInstance');
        $firstTenant->shouldReceive('makeCurrent')->once();

        $secondTenant = Mockery::mock('Modules\GlobalAdmin\Models\TenantInstance');
        $secondTenant->shouldReceive('makeCurrent')->once();

        $this->tenantMock->shouldReceive('find')->with($firstTenantId)->once()->andReturn($firstTenant);
        $this->tenantMock->shouldReceive('find')->with($secondTenantId)->once()->andReturn($secondTenant);

        $firstPayload = Mockery::mock(Payload::class);
        $firstPayload->shouldReceive('get')->with('tenant_id')->once()->andReturn($firstTenantId);

        $secondPayload = Mockery::mock(Payload::class);
        $secondPayload->shouldReceive('get')->with('tenant_id')->once()->andReturn($secondTenantId);

        JWTAuth::shouldReceive('getToken')->twice()->andReturn('token-1', 'token-2');
        JWTAuth::shouldReceive('setToken')->twice()->andReturnSelf();
        JWTAuth::shouldReceive('getPayload')->twice()->andReturn($firstPayload, $secondPayload);

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('OK');
        };

        $firstRequest = Request::create('/api/test1', 'GET');
        $secondRequest = Request::create('/api/test2', 'GET');

        $firstResponse = $middleware->handle($firstRequest, $next);
        $secondResponse = $middleware->handle($secondRequest, $next);

        $this->assertEquals('OK', $firstResponse->getContent());
        $this->assertEquals('OK', $secondResponse->getContent());
    }

    public function test_middleware_does_not_interfere_with_regular_requests(): void
    {
        $request = Request::create('/api/test', 'GET');

        JWTAuth::shouldReceive('getToken')->once()->andReturn(null);

        $middleware = new TenancyByJwtToken;
        $next = function ($request) {
            return new Response('Request processed normally');
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals('Request processed normally', $response->getContent());
    }
}
