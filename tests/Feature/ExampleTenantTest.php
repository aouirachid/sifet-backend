<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Example test demonstrating tenant-aware testing
 *
 * This example shows how to:
 * - Create a test tenant
 * - Run tests in tenant context
 * - Test multi-tenant scenarios
 */
class ExampleTenantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that runs in the landlord database context
     */
    public function test_landlord_database_connection(): void
    {
        // This test runs against the landlord database
        // You can test tenant creation, domain management, etc.

        $this->assertDatabaseConnection('landlord');
    }

    /**
     * Test that creates and uses a tenant
     */
    public function test_tenant_context(): void
    {
        // Create a test tenant and make it current
        $tenant = $this->createAndActAsTenant([
            'data' => [
                'name' => 'Test Company',
                'plan' => 'enterprise',
            ],
        ]);

        // Now you're in the tenant context
        // Any database operations will use the tenant database
        $this->assertNotNull($tenant->id);
        $this->assertEquals('Test Company', $tenant->data['name']);

        // You can now test tenant-specific functionality
        // For example, creating companies, invoices, fleet items, etc.
    }

    /**
     * Test multiple tenants in isolation
     */
    public function test_tenant_isolation(): void
    {
        // Create first tenant
        $tenant1 = $this->createAndActAsTenant([
            'data' => ['name' => 'Tenant One'],
        ]);

        // Create some data for tenant 1
        // $company1 = Company::create(['name' => 'Company A']);

        // Switch to second tenant
        $tenant2 = $this->createAndActAsTenant([
            'data' => ['name' => 'Tenant Two'],
        ]);

        // Data from tenant 1 should not be accessible
        // $this->assertCount(0, Company::all());

        $this->assertNotEquals($tenant1->id, $tenant2->id);
    }

    /**
     * Helper method to assert current database connection
     */
    protected function assertDatabaseConnection(string $expected): void
    {
        $actual = config('database.default');
        $this->assertEquals($expected, $actual, "Expected database connection: {$expected}, got: {$actual}");
    }
}
