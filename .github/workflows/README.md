# GitHub Actions CI/CD Pipeline

This directory contains the GitHub Actions workflow configuration for the SIFET Backend project.

## 🏗️ Architecture Overview

This project uses a **multi-tenant (multi-database)** and **modular** architecture:

- **Multi-Tenancy**: Separate databases for landlord (global) and tenant-specific data
  - **Landlord DB**: Stores tenants, domains, and global configuration
  - **Tenant DBs**: Each tenant has their own database for isolated data
- **Modular Design**: Business logic organized into independent modules:
  - GlobalAdmin, CompanyManagement, FinanceBilling, FleetManagement, OrderProcessing, StoreManagement

## 📋 Workflow: CI Pipeline (`ci.yml`)

### Triggers
- **Push**: Runs on push to all branches
- **Pull Request**: Runs on PRs targeting `main` or `develop` branches

### Services
- **PostgreSQL 16 with PostGIS 3.4**: For spatial database operations (both landlord and tenant DBs)
- **Redis 7**: For caching and queue operations

### Quality Gates (Sequential)

1. **🎨 Code Formatting (Laravel Pint)**
   - Command: `vendor/bin/pint --test`
   - Ensures code follows Laravel coding standards
   - Fails if code is not properly formatted

2. **🔍 Static Analysis (PHPStan Level 3 via Larastan)**
   - Command: `vendor/bin/phpstan analyse --memory-limit=2G`
   - Analyzes code for type safety and potential bugs
   - Configuration: `phpstan.neon`

3. **🔒 Security Audit**
   - Command: `composer audit`
   - Checks for known vulnerabilities in dependencies

4. **⚠️ N+1 Query Detection**
   - Method: `Model::automaticallyEagerLoadRelationships()` (Laravel 12.8+)
   - Configured in: `tests/TestCase.php`
   - Tests will fail if lazy loading/N+1 queries are detected

5. **✅ Parallel Testing (Pest)**
   - Command: `vendor/bin/pest --parallel --processes=4`
   - Runs all tests in parallel for faster execution
   - Uses PostgreSQL test database

## 🔧 Configuration Files

### Modified Files
- **`composer.json`**: Updated PHP to 8.4, added Larastan & PHPStan
- **`tests/TestCase.php`**: Added N+1 detection and tenant-aware testing helpers
- **`phpunit.xml`**: Configured multi-tenant databases and all module test suites
- **`phpstan.neon`**: PHPStan level 3 configuration with module scanning

### New Files
- **`.github/workflows/ci.yml`**: Main CI workflow with multi-tenant support

## 🏢 Multi-Tenant Testing

The CI pipeline handles both landlord and tenant databases:

### Database Setup
1. **Landlord Database** (`sifet_test_landlord`): 
   - Runs core migrations
   - Runs GlobalAdmin module migrations (tenants, domains)
   - Seeds with test data

2. **Tenant Database** (`sifet_test_tenant`):
   - Runs all module migrations (CompanyManagement, FinanceBilling, etc.)
   - Used for tenant-specific tests

### Test Helpers
The `TestCase` class provides tenant-aware testing methods:

```php
// Create and activate a test tenant
$tenant = $this->createAndActAsTenant([
    'data' => ['name' => 'Acme Corp']
]);

// Run tenant migrations if needed
protected function shouldRunTenantMigrations(): bool
{
    return true; // Override in your test class
}
```

## 🚀 Next Steps

1. **Install Dependencies**
   ```bash
   composer update
   ```

2. **Create `.env.example`** (if not exists)
   Ensure it contains multi-tenant PostgreSQL and Redis configuration:
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
   ```

3. **Test Locally** (Optional)
   ```bash
   # Run linting
   vendor/bin/pint --test
   
   # Run static analysis
   vendor/bin/phpstan analyse --memory-limit=2G
   
   # Run security audit
   composer audit
   
   # Run tests
   vendor/bin/pest --parallel
   ```

4. **Push to GitHub**
   ```bash
   git add .
   git commit -m "feat: add CI/CD pipeline with quality gates"
   git push origin your-branch
   ```

5. **Create a Pull Request**
   - The workflow will automatically run
   - All quality gates must pass before merging

## 📊 Workflow Features

- ✅ PHP 8.4 support
- ✅ Composer dependency caching for faster builds
- ✅ **Multi-tenant database setup** (landlord + tenant)
- ✅ **All modules tested** (GlobalAdmin, CompanyManagement, FinanceBilling, FleetManagement, OrderProcessing, StoreManagement)
- ✅ PostgreSQL 16 with PostGIS 3.4 for spatial operations
- ✅ Redis 7 for caching
- ✅ Parallel test execution (4 processes)
- ✅ GitHub annotations for PHPStan errors
- ✅ Comprehensive test summary in GitHub Actions UI
- ✅ N+1 query detection (Laravel 12.8+ `automaticallyEagerLoadRelationships()`)

## 🐛 Troubleshooting

### Pint Formatting Failures
Run `vendor/bin/pint` locally to auto-fix formatting issues.

### PHPStan Errors
- Review errors and fix type hints
- Adjust level in `phpstan.neon` if needed (currently level 3)
- Add ignored errors if false positives occur

### N+1 Query Detection
- Ensure all relationships are eager loaded in queries
- Use `->with(['relation'])` in your queries
- Check test output for lazy loading warnings

### Test Failures
- Ensure migrations run successfully
- Verify database seeds are working
- Check PostgreSQL/Redis connectivity

## 📝 Notes

- The workflow uses PostgreSQL with PostGIS instead of SQLite for better production parity
- Tests run in parallel for faster execution (4 processes by default)
- All quality gates must pass sequentially - if one fails, subsequent steps are skipped
- The N+1 detection feature requires Laravel 12.8+ with the new `automaticallyEagerLoadRelationships()` method
