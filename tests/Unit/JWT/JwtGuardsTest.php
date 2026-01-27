<?php

declare(strict_types=1);

namespace Tests\Unit\JWT;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Config;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Tests\TestCase;

class JwtGuardsTest extends TestCase
{
    public function test_api_guard_is_configured_with_jwt_driver(): void
    {
        $guards = Config::get('auth.guards');

        $this->assertArrayHasKey('api', $guards, 'API guard must be configured');

        $apiGuard = $guards['api'];
        $this->assertEquals('jwt', $apiGuard['driver'], 'API guard must use JWT driver');
        $this->assertEquals('users', $apiGuard['provider'], 'API guard must use users provider');
        $this->assertEquals('users', $apiGuard['provider'], 'API guard must use users provider');
    }

    public function test_landlord_guard_is_configured_with_jwt_driver(): void
    {
        $guards = Config::get('auth.guards');

        $this->assertArrayHasKey('landlord', $guards, 'Landlord guard must be configured');

        $landlordGuard = $guards['landlord'];
        $this->assertEquals('jwt', $landlordGuard['driver'], 'Landlord guard must use JWT driver');
        $this->assertEquals('admins', $landlordGuard['provider'], 'Landlord guard must use admins provider');
    }

    public function test_tenant_guard_is_configured_with_jwt_driver(): void
    {
        $guards = Config::get('auth.guards');

        $this->assertArrayHasKey('tenant', $guards, 'Tenant guard must be configured');

        $tenantGuard = $guards['tenant'];
        $this->assertEquals('jwt', $tenantGuard['driver'], 'Tenant guard must use JWT driver');
        $this->assertEquals('company_users', $tenantGuard['provider'], 'Tenant guard must use company_users provider');
    }

    public function test_web_guard_uses_session_driver(): void
    {
        $guards = Config::get('auth.guards');

        $this->assertArrayHasKey('web', $guards, 'Web guard must be configured');

        $webGuard = $guards['web'];
        $this->assertEquals('session', $webGuard['driver'], 'Web guard must use session driver');
        $this->assertEquals('users', $webGuard['provider'], 'Web guard must use users provider');
    }

    public function test_all_jwt_guards_use_jwt_driver(): void
    {
        $guards = Config::get('auth.guards');
        $jwtGuardNames = ['api', 'landlord', 'tenant'];

        foreach ($jwtGuardNames as $guardName) {
            $this->assertArrayHasKey($guardName, $guards, "Guard '{$guardName}' must be configured");
            $this->assertEquals(
                'jwt',
                $guards[$guardName]['driver'],
                "Guard '{$guardName}' must use JWT driver"
            );
        }
    }

    public function test_jwt_guards_have_correct_providers(): void
    {
        $guards = Config::get('auth.guards');

        $this->assertEquals('users', $guards['api']['provider'], 'API guard should use users provider');
        $this->assertEquals('admins', $guards['landlord']['provider'], 'Landlord guard should use admins provider');
        $this->assertEquals('company_users', $guards['tenant']['provider'], 'Tenant guard should use company_users provider');
    }

    public function test_jwt_guards_can_be_resolved(): void
    {
        $auth = app(AuthManager::class);

        $apiGuard = $auth->guard('api');
        $this->assertInstanceOf(JWTGuard::class, $apiGuard, 'API guard should resolve to JWTGuard instance');

        $landlordGuard = $auth->guard('landlord');
        $this->assertInstanceOf(JWTGuard::class, $landlordGuard, 'Landlord guard should resolve to JWTGuard instance');

        $tenantGuard = $auth->guard('tenant');
        $this->assertInstanceOf(JWTGuard::class, $tenantGuard, 'Tenant guard should resolve to JWTGuard instance');
    }

    public function test_jwt_guards_have_different_providers(): void
    {
        $auth = app(AuthManager::class);

        $apiGuard = $auth->guard('api');
        $landlordGuard = $auth->guard('landlord');
        $tenantGuard = $auth->guard('tenant');

        $apiProvider = $apiGuard->getProvider();
        $landlordProvider = $landlordGuard->getProvider();
        $tenantProvider = $tenantGuard->getProvider();

        // Compare the Eloquent models configured for each provider
        $this->assertNotEquals(
            $apiProvider->getModel(),
            $landlordProvider->getModel(),
            'API and Landlord guards should use different user models'
        );

        $this->assertNotEquals(
            $apiProvider->getModel(),
            $tenantProvider->getModel(),
            'API and Tenant guards should use different user models'
        );

        $this->assertNotEquals(
            $landlordProvider->getModel(),
            $tenantProvider->getModel(),
            'Landlord and Tenant guards should use different user models'
        );
    }

    public function test_default_auth_guard_is_web(): void
    {
        $defaultGuard = Config::get('auth.defaults.guard');

        $this->assertEquals('web', $defaultGuard, 'Default auth guard should be web');
    }

    public function test_jwt_guards_are_configured_for_multi_tenancy(): void
    {
        $guards = Config::get('auth.guards');

        $landlordGuard = $guards['landlord'];
        $tenantGuard = $guards['tenant'];

        $this->assertArrayHasKey('provider', $landlordGuard, 'Landlord guard must have provider');
        $this->assertArrayHasKey('provider', $tenantGuard, 'Tenant guard must have provider');

        $this->assertEquals('admins', $landlordGuard['provider'], 'Landlord guard should use admins provider');
        $this->assertEquals('company_users', $tenantGuard['provider'], 'Tenant guard should use company_users provider');
    }
}
