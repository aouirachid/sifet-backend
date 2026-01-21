<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Modules\GlobalAdmin\Models\Tenant;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Enable automatic eager loading to prevent N+1 queries in tests
        // This will throw an exception if lazy loading is detected
        Model::automaticallyEagerLoadRelationships();
    }

    /**
     * Create a test tenant and make it current for testing
     */
    protected function createAndActAsTenant(array $attributes = []): Tenant
    {
        $defaultAttributes = [
            'id' => 'test-tenant-'.uniqid(),
            'database_name' => env('TENANT_DB_DATABASE', 'sifet_test_tenant'),
            'data' => array_merge([
                'name' => 'Test Tenant',
            ], $attributes['data'] ?? []),
        ];

        $tenant = Tenant::create(array_merge($defaultAttributes, $attributes));

        // Make this tenant current
        $tenant->makeCurrent();

        // Run tenant migrations if needed
        if (method_exists($this, 'runTenantMigrations') && $this->shouldRunTenantMigrations()) {
            $this->runTenantMigrations($tenant);
        }

        return $tenant;
    }

    /**
     * Check if tenant migrations should be run (override in test classes as needed)
     */
    protected function shouldRunTenantMigrations(): bool
    {
        return false;
    }

    /**
     * Run migrations for the tenant database
     */
    protected function runTenantMigrations(Tenant $tenant): void
    {
        Artisan::call('tenants:artisan', [
            'artisanCommand' => 'migrate --database=tenant',
            '--tenant' => $tenant->id,
        ]);
    }

    /**
     * Forget the current tenant after tests
     */
    protected function tearDown(): void
    {
        // Forget current tenant to clean up
        if (app()->bound('currentTenant')) {
            optional(app('currentTenant'))->forget();
        }

        parent::tearDown();
    }
}
