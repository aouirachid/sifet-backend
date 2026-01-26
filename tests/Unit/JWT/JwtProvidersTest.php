<?php

declare(strict_types=1);

namespace Tests\Unit\JWT;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Auth\EloquentUserProvider;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use App\Models\User;
use Modules\GlobalAdmin\Models\Admin;
use Modules\CompanyManagement\Models\CompanyUser;

class JwtProvidersTest extends TestCase
{
    public function test_users_provider_is_configured(): void
    {
        $providers = Config::get('auth.providers');
        
        $this->assertArrayHasKey('users', $providers, 'Users provider must be configured');
        
        $usersProvider = $providers['users'];
        $this->assertEquals('eloquent', $usersProvider['driver'], 'Users provider must use eloquent driver');
        $this->assertEquals(User::class, $usersProvider['model'], 'Users provider must use User model');
    }

    public function test_admins_provider_is_configured(): void
    {
        $providers = Config::get('auth.providers');
        
        $this->assertArrayHasKey('admins', $providers, 'Admins provider must be configured');
        
        $adminsProvider = $providers['admins'];
        $this->assertEquals('eloquent', $adminsProvider['driver'], 'Admins provider must use eloquent driver');
        $this->assertEquals(Admin::class, $adminsProvider['model'], 'Admins provider must use Admin model');
    }

    public function test_company_users_provider_is_configured(): void
    {
        $providers = Config::get('auth.providers');
        
        $this->assertArrayHasKey('company_users', $providers, 'Company users provider must be configured');
        
        $companyUsersProvider = $providers['company_users'];
        $this->assertEquals('eloquent', $companyUsersProvider['driver'], 'Company users provider must use eloquent driver');
        $this->assertEquals(
            CompanyUser::class, 
            $companyUsersProvider['model'], 
            'Company users provider must use CompanyUser model'
        );
    }

    public function test_all_providers_use_eloquent_driver(): void
    {
        $providers = Config::get('auth.providers');
        $providerNames = ['users', 'admins', 'company_users'];
        
        foreach ($providerNames as $providerName) {
            $this->assertArrayHasKey($providerName, $providers, "Provider '{$providerName}' must be configured");
            $this->assertEquals(
                'eloquent',
                $providers[$providerName]['driver'],
                "Provider '{$providerName}' must use eloquent driver"
            );
        }
    }

    public function test_providers_have_correct_model_classes(): void
    {
        $providers = Config::get('auth.providers');
        
        $this->assertEquals(
            User::class,
            $providers['users']['model'],
            'Users provider should use App\\Models\\User'
        );
        
        $this->assertEquals(
            Admin::class,
            $providers['admins']['model'],
            'Admins provider should use Modules\\GlobalAdmin\\Models\\Admin'
        );
        
        $this->assertEquals(
            CompanyUser::class,
            $providers['company_users']['model'],
            'Company users provider should use Modules\\CompanyManagement\\Models\\CompanyUser'
        );
    }

    public function test_model_classes_exist(): void
    {
        $providers = Config::get('auth.providers');
        
        foreach ($providers as $providerName => $providerConfig) {
            $modelClass = $providerConfig['model'];
            $this->assertTrue(
                class_exists($modelClass),
                "Model class '{$modelClass}' for provider '{$providerName}' must exist"
            );
        }
    }

    public function test_models_implement_jwt_subject(): void
    {
        $providers = Config::get('auth.providers');
        
        foreach ($providers as $providerName => $providerConfig) {
            $modelClass = $providerConfig['model'];
            
            $this->assertTrue(
                class_exists($modelClass),
                "Model class '{$modelClass}' must exist"
            );

            $this->assertTrue(
                is_subclass_of($modelClass, JWTSubject::class),
                "Model '{$modelClass}' for provider '{$providerName}' must implement JWTSubject"
            );
        }
    }

    public function test_providers_can_be_resolved(): void
    {
        $auth = app('auth');
        
        $usersProvider = $auth->createUserProvider('users');
        $this->assertInstanceOf(
            EloquentUserProvider::class,
            $usersProvider,
            'Users provider should resolve to EloquentUserProvider'
        );
        
        $adminsProvider = $auth->createUserProvider('admins');
        $this->assertInstanceOf(
            EloquentUserProvider::class,
            $adminsProvider,
            'Admins provider should resolve to EloquentUserProvider'
        );
        
        $companyUsersProvider = $auth->createUserProvider('company_users');
        $this->assertInstanceOf(
            EloquentUserProvider::class,
            $companyUsersProvider,
            'Company users provider should resolve to EloquentUserProvider'
        );
    }

    public function test_provider_models_have_jwt_methods(): void
    {
        $providers = Config::get('auth.providers');
        
        foreach ($providers as $providerName => $providerConfig) {
            $modelClass = $providerConfig['model'];
            
            $this->assertTrue(
                class_exists($modelClass),
                "Model class '{$modelClass}' must exist"
            );

            $this->assertTrue(
                method_exists($modelClass, 'getJWTIdentifier'),
                "Model '{$modelClass}' must have getJWTIdentifier method"
            );
            
            $this->assertTrue(
                method_exists($modelClass, 'getJWTCustomClaims'),
                "Model '{$modelClass}' must have getJWTCustomClaims method"
            );
        }
    }

    public function test_providers_are_multi_tenant_aware(): void
    {
        $providers = Config::get('auth.providers');
        
        $this->assertArrayHasKey('users', $providers, 'Users provider for global access');
        $this->assertArrayHasKey('admins', $providers, 'Admins provider for landlord access');
        $this->assertArrayHasKey('company_users', $providers, 'Company users provider for tenant access');
    }

    public function test_provider_model_relationships(): void
    {
        $providers = Config::get('auth.providers');
        
        foreach ($providers as $providerName => $providerConfig) {
            $modelClass = $providerConfig['model'];
            
            $this->assertTrue(
                class_exists($modelClass),
                "Model class '{$modelClass}' must exist"
            );

            $modelInstance = new $modelClass;
            
            if ($providerName === 'company_users') {
                $this->assertTrue(
                    method_exists($modelInstance, 'company'),
                    "CompanyUser model should have company relationship"
                );
            }
            
            if ($providerName === 'users') {
                $this->assertTrue(
                    method_exists($modelInstance, 'tenants'),
                    "User model should have tenants relationship"
                );
            }
        }
    }
}