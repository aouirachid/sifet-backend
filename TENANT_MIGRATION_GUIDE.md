# Transactional Tenant Migration Documentation

## Overview
The `tenants:migrate` command provides a safe, transactional way to run migrations across multiple tenant databases. Unlike standard migration commands, it wraps each tenant's migration process in a database transaction to ensure atomicity and data integrity.

## Key Features
- **Transactional Safety**: Each tenant's migration is wrapped in `DB::beginTransaction()`. If a migration fails for a specific tenant, it is rolled back without affecting other tenants or leaving the database in a partially migrated state.
  - **Note**: Full transactional rollback is only guaranteed on databases that support transactional DDL (e.g., PostgreSQL). On MySQL/MariaDB, DDL statements cause implicit commits and cannot be rolled back.
- **Error Logging**: Failures are automatically logged to the Laravel log files with the tenant ID and specific exception details.
- **Tenant Filtering**: Ability to run migrations for all tenants or a specific subset.
- **Standard Options**: Supports Laravel-standard flags like `--fresh` and `--seed`.

## Command Signature
```bash
php artisan tenants:migrate {--tenant=* : Target specific tenant IDs} {--fresh : Drop all tables first} {--seed : Run seeders after migration}
```

## Usage Examples

### Run Migrations for All Tenants
Automatically loops through all registered tenants and applies pending migrations.
```bash
php artisan tenants:migrate
```

### Run with Seeding
Applies migrations and then runs the database seeders for each tenant.
```bash
php artisan tenants:migrate --seed
```

### Fresh Migration
Drops all tables and re-runs all migrations for every tenant (use with caution).
```bash
php artisan tenants:migrate --fresh
```

### Target Specific Tenants
To migrate only specific tenants, use multiple `--tenant` flags.
```bash
php artisan tenants:migrate --tenant=tenant-uuid-1 --tenant=tenant-uuid-2
```

## Technical details
- **Trait**: Uses `Spatie\Multitenancy\Commands\Concerns\TenantAware` for robust tenant iteration.
- **Connection**: Operates on the connection defined in `multitenancy.tenant_database_connection_name` (default: `tenant`).
- **Logic Location**: `Modules/GlobalAdmin/app/Console/SafeMigrateCommand.php`.

## Error Handling
If a tenant migration fails:
1. The database transaction for that specific tenant is **rolled back**.
2. An error message is printed to the console.
3. A detailed error log is written to the configured Laravel log file (typically `storage/logs/`) including:
    - Tenant ID
    - Stack trace
    - Database name
4. The command continues to the next tenant (if any).
