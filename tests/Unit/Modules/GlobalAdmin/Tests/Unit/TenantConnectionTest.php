<?php

use Modules\GlobalAdmin\Models\Tenant;

it('returns the correct database name', function () {
    $tenant = new Tenant([
        'id' => 'test-tenant',
        'database_name' => 'tenant_test_db',
        'data' => ['name' => 'Test Tenant']
    ]);

    expect($tenant->getDatabaseName())->toBe('tenant_test_db');
});

it('has correct fillable attributes', function () {
    $tenant = new Tenant();

    expect($tenant->getFillable())->toContain('id', 'data', 'database_name');
});

it('casts data attribute to array', function () {
    $tenant = new Tenant([
        'id' => 'test-tenant',
        'database_name' => 'tenant_test_db',
        'data' => ['key' => 'value']
    ]);

    expect($tenant->data)->toBeArray();
    expect($tenant->data)->toHaveKey('key');
});
