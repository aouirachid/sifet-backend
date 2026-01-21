# Multi-Tenant & Modular CI/CD Setup - Summary

## 🎯 Overview

This document summarizes the GitHub Actions CI/CD pipeline configured for the SIFET Backend project, which uses a **multi-tenant (multi-database)** and **modular** architecture.

## 🏗️ Architecture

### Multi-Tenancy
- **Landlord Database**: Stores tenants, domains, and global configuration
- **Tenant Databases**: Each tenant has their own isolated database
- **Package**: `spatie/laravel-multitenancy`

### Modular Design
- **Package**: `nwidart/laravel-modules`
- **Modules**:
  - GlobalAdmin (Landlord-level)
  - CompanyManagement (Tenant-level)
  - FinanceBilling (Tenant-level)
  - FleetManagement (Tenant-level)
  - OrderProcessing (Tenant-level)
  - StoreManagement (Tenant-level)

## 📝 Changes Made

### 1. **composer.json**
```diff
- "php": "^8.2"
+ "php": "^8.4"

+ "larastan/larastan": "^3.0"
+ "phpstan/phpstan": "^2.0"
```

### 2. **phpstan.neon** (New File)
- PHPStan Level 3 configuration
- Includes Larastan for Laravel-specific analysis
- Scans both `app/` and `Modules/` directories
- Excludes vendor and node_modules from modules

### 3. **phpunit.xml**
**Added:**
- Test suites for all 6 modules
- Multi-tenant database configuration (landlord + tenant)
- Redis configuration for caching
- PostgreSQL as default database (instead of SQLite)

**Key Environment Variables:**
```xml
<env name="DB_CONNECTION" value="landlord"/>
<env name="LANDLORD_DB_DATABASE" value="sifet_test_landlord"/>
<env name="TENANT_DB_DATABASE" value="sifet_test_tenant"/>
<env name="CACHE_DRIVER" value="redis"/>
```

### 4. **tests/TestCase.php**
**Added Features:**
- `Model::automaticallyEagerLoadRelationships()` for N+1 detection (Laravel 12.8+)
- `createAndActAsTenant()` - Helper to create and activate test tenants
- `shouldRunTenantMigrations()` - Control migration behavior per test
- `runTenantMigrations()` - Run migrations for tenant database
- Automatic tenant cleanup in `tearDown()`

**Usage Example:**
```php
public function test_something_in_tenant_context(): void
{
    $tenant = $this->createAndActAsTenant([
        'data' => ['name' => 'Test Company']
    ]);
    
    // Your test code here - runs in tenant context
}
```

### 5. **.github/workflows/ci.yml** (New File)
**Comprehensive CI Pipeline with:**

#### Services
- PostgreSQL 16 with PostGIS 3.4
- Redis 7

#### Database Setup
1. Creates both `sifet_test_landlord` and `sifet_test_tenant` databases
2. Enables PostGIS extension on both
3. Runs landlord migrations
4. Runs GlobalAdmin module migrations (tenants, domains)
5. Runs all tenant module migrations
6. Seeds landlord database

#### Quality Gates (Sequential)
1. 🎨 **Code Formatting** - `vendor/bin/pint --test`
2. 🔍 **Static Analysis** - `vendor/bin/phpstan analyse --memory-limit=2G`
3. 🔒 **Security Audit** - `composer audit`
4. ⚠️ **N+1 Detection** - Via `automaticallyEagerLoadRelationships()`
5. ✅ **Tests** - `vendor/bin/pest --parallel --processes=4`

### 6. **tests/Feature/ExampleTenantTest.php** (New File)
Example test file demonstrating:
- Testing in landlord context
- Creating and using test tenants
- Testing tenant isolation
- Multi-tenant scenarios

## 🚀 Getting Started

### Step 1: Update Dependencies
```bash
composer update
```

This will install:
- Larastan ^3.0
- PHPStan ^2.0
- Update PHP requirement to 8.4

### Step 2: Test Locally (Optional)

```bash
# Code formatting
vendor/bin/pint --test

# Static analysis
vendor/bin/phpstan analyse --memory-limit=2G

# Security audit
composer audit

# Run tests
vendor/bin/pest --parallel
```

### Step 3: Update .env.example

Ensure it contains multi-tenant configuration:

```env
DB_CONNECTION=landlord
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sifet_landlord
DB_USERNAME=sifet
DB_PASSWORD=secret

LANDLORD_DB_DATABASE=sifet_landlord
TENANT_DB_DATABASE=tenant_default

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_DRIVER=redis
```

### Step 4: Push to GitHub

```bash
git add .
git commit -m "feat: add multi-tenant CI/CD pipeline with quality gates"
git push origin your-branch
```

### Step 5: Create Pull Request

The CI pipeline will automatically:
- Run all quality checks
- Setup landlord and tenant databases
- Run all module tests
- Report results in GitHub Actions UI

## 📊 CI Pipeline Flow

```
1. Checkout Code
2. Setup PHP 8.4
3. Cache Composer Dependencies
4. Install Dependencies
5. Setup Environment
6. Create Landlord & Tenant Databases
7. Enable PostGIS Extension
8. Run Landlord Migrations
9. Run GlobalAdmin Migrations
10. Seed Landlord Database
11. Run Tenant Module Migrations
12. ✓ Code Formatting Check
13. ✓ Static Analysis (PHPStan Level 3)
14. ✓ Security Audit
15. ✓ Run All Tests (with N+1 detection)
16. Generate Test Summary
```

## 🧪 Testing Strategy

### Landlord Tests
- Test tenant creation/management
- Test domain management
- Test global configuration
- Use `DB_CONNECTION=landlord`

### Tenant Tests
- Create test tenant via `createAndActAsTenant()`
- Test tenant-specific modules (Company, Finance, Fleet, etc.)
- Test data isolation between tenants
- Use tenant database connection

### Module Tests
Each module has its own test suite:
- `tests/Unit` - Unit tests
- `tests/Feature` - Feature tests
- Run via `vendor/bin/pest Modules/ModuleName/tests`

## 🔧 Configuration Files Summary

| File | Purpose | Changes |
|------|---------|---------|
| `composer.json` | Dependencies | PHP 8.4, Larastan, PHPStan |
| `phpstan.neon` | Static Analysis | Level 3, Modules included |
| `phpunit.xml` | Test Configuration | Multi-tenant DBs, all modules |
| `tests/TestCase.php` | Test Base Class | N+1 detection, tenant helpers |
| `.github/workflows/ci.yml` | CI Pipeline | Full multi-tenant workflow |
| `tests/Feature/ExampleTenantTest.php` | Example | Tenant testing patterns |

## 🎯 Quality Gates

All PRs must pass these gates:

✅ **Code Formatting** - Laravel Pint standards  
✅ **Static Analysis** - PHPStan Level 3 (no errors)  
✅ **Security Audit** - No vulnerable dependencies  
✅ **N+1 Detection** - No lazy loading in tests  
✅ **All Tests** - 100% pass rate  

## 🐛 Troubleshooting

### Issue: PHPStan Errors After Update
**Solution**: Run `composer update` first, then `vendor/bin/phpstan clear-result-cache`

### Issue: Tenant Migrations Failing in CI
**Solution**: Check that module migration paths are correct and migrations are compatible with PostgreSQL + PostGIS

### Issue: Tests Fail Locally But Pass in CI
**Solution**: 
1. Check your local database configuration
2. Ensure you're using PostgreSQL (not SQLite)
3. Run migrations for both landlord and tenant databases

### Issue: Parallel Tests Cause Database Conflicts
**Solution**: Pest's parallel mode creates separate processes. Ensure your tests are isolated and don't share state.

## 📚 Additional Resources

- [Spatie Multi-Tenancy Docs](https://spatie.be/docs/laravel-multitenancy)
- [Laravel Modules Docs](https://laravelmodules.com/)
- [Larastan Docs](https://github.com/larastan/larastan)
- [Laravel Pint Docs](https://laravel.com/docs/pint)
- [Pest PHP Docs](https://pestphp.com/)

## 🎉 Summary

The CI/CD pipeline is now fully configured for:
- ✅ PHP 8.4
- ✅ Multi-tenant architecture (landlord + tenant databases)
- ✅ All 6 modules tested independently
- ✅ PostgreSQL 16 with PostGIS 3.4
- ✅ Redis 7 for caching
- ✅ N+1 query detection (Laravel 12.8+)
- ✅ PHPStan Level 3 static analysis
- ✅ Security auditing
- ✅ Code formatting enforcement
- ✅ Parallel test execution

**All quality gates are enforced on every Pull Request!** 🚀
