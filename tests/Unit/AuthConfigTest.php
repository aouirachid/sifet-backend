<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuthConfigTest extends TestCase
{
    /**
     * Test that the landlord and tenant guards are correctly configured.
     */
    public function test_auth_guards_are_configured(): void
    {
        $guards = Config::get('auth.guards');

        $this->assertArrayHasKey('landlord', $guards);
        $this->assertEquals('jwt', $guards['landlord']['driver']);
        $this->assertEquals('admins', $guards['landlord']['provider']);

        $this->assertArrayHasKey('tenant', $guards);
        $this->assertEquals('jwt', $guards['tenant']['driver']);
        $this->assertEquals('company_users', $guards['tenant']['provider']);
    }

    /**
     * Test that the providers are correctly configured.
     */
    public function test_auth_providers_are_configured(): void
    {
        $providers = Config::get('auth.providers');

        $this->assertArrayHasKey('admins', $providers);
        $this->assertEquals('eloquent', $providers['admins']['driver']);
        $this->assertEquals(\Modules\GlobalAdmin\Models\Admin::class, $providers['admins']['model']);

        $this->assertArrayHasKey('company_users', $providers);
        $this->assertEquals('eloquent', $providers['company_users']['driver']);
        $this->assertEquals(\Modules\CompanyManagement\Models\CompanyUser::class, $providers['company_users']['model']);
    }
}
