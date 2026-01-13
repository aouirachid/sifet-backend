# CI/CD Quick Reference Card

## 🚀 Quick Start

### 1. Update Dependencies (Do This First!)
```bash
composer update
```

### 2. Local Testing Commands
```bash
# Fix code formatting
vendor/bin/pint

# Check formatting (CI mode)
vendor/bin/pint --test

# Run static analysis
vendor/bin/phpstan analyse --memory-limit=2G

# Security check
composer audit

# Run all tests
vendor/bin/pest

# Run tests in parallel
vendor/bin/pest --parallel

# Run specific module tests
vendor/bin/pest Modules/CompanyManagement/tests
```

## 🏢 Multi-Tenant Testing

### Create a Test Tenant
```php
use Tests\TestCase;

class MyTest extends TestCase
{
    public function test_example(): void
    {
        // Create and activate a test tenant
        $tenant = $this->createAndActAsTenant([
            'data' => [
                'name' => 'Test Company',
                'plan' => 'enterprise'
            ]
        ]);
        
        // Now you're in tenant context
        // All DB operations use tenant database
    }
}
```

### Test Landlord Operations
```php
public function test_landlord_operation(): void
{
    // Tests run in landlord DB by default
    $tenant = Tenant::create([...]);
    $domain = Domain::create([...]);
}
```

## 📊 CI Pipeline Status

### Quality Gates (Must Pass All)
- ✅ Code Formatting (Pint)
- ✅ Static Analysis (PHPStan Level 3)
- ✅ Security Audit
- ✅ N+1 Query Detection
- ✅ All Tests (Parallel Execution)

### Databases in CI
- **Landlord**: `sifet_test_landlord` (tenants, domains, global data)
- **Tenant**: `sifet_test_tenant` (module data, tenant-specific)

### Services
- PostgreSQL 16 + PostGIS 3.4
- Redis 7

## 🐛 Common Issues & Fixes

### "Class not found" errors during development
```bash
composer dump-autoload
```

### PHPStan errors after update
```bash
vendor/bin/phpstan clear-result-cache
composer dump-autoload
```

### Code formatting failures
```bash
# Auto-fix all issues
vendor/bin/pint

# Check what would be fixed
vendor/bin/pint --test
```

### N+1 Query Detection
If you get lazy loading errors:
```php
// ❌ Bad - causes N+1
$users = User::all();
foreach ($users as $user) {
    echo $user->company->name; // Lazy loads company
}

// ✅ Good - eager loads
$users = User::with('company')->get();
foreach ($users as $user) {
    echo $user->company->name;
}
```

### Tests failing with database errors
```bash
# Refresh migrations
php artisan migrate:fresh --seed

# Check database connection
php artisan db:show
```

## 📁 File Locations

| File | Purpose |
|------|---------|
| `.github/workflows/ci.yml` | CI pipeline configuration |
| `phpstan.neon` | Static analysis config |
| `phpunit.xml` | Test configuration |
| `pint.json` | Code style config |
| `tests/TestCase.php` | Base test class with tenant helpers |

## 🎯 Module Testing

### Test a Single Module
```bash
vendor/bin/pest Modules/CompanyManagement/tests
vendor/bin/pest Modules/FinanceBilling/tests
vendor/bin/pest Modules/FleetManagement/tests
```

### Test All Modules
```bash
vendor/bin/pest
```

## ⚙️ PHPStan Configuration

### Current Level: 3
- Levels range from 0 (loosest) to 9 (strictest)
- Level 3 catches most common bugs
- Configured in `phpstan.neon`

### Ignore False Positives
Add to `phpstan.neon`:
```neon
parameters:
    ignoreErrors:
        - '#specific error message#'
```

## 🔄 CI Workflow Triggers

### Automatically Runs On:
- Push to any branch
- Pull request to `main`
- Pull request to `develop`

### Manual Trigger (if needed):
Go to GitHub Actions → Select workflow → Run workflow

## 📝 Before Creating a PR

**Checklist:**
```bash
# 1. Format code
vendor/bin/pint

# 2. Run static analysis
vendor/bin/phpstan analyse

# 3. Check security
composer audit

# 4. Run tests
vendor/bin/pest --parallel

# 5. Check for uncommitted changes
git status
```

## 🎉 Success Indicators

### CI Passed When You See:
- ✅ All green checkmarks in GitHub
- ✅ "All quality gates passed!" message
- ✅ Test summary shows module counts
- ✅ No security vulnerabilities
- ✅ No PHPStan errors

## 🆘 Need Help?

1. Check `MULTI_TENANT_CI_SETUP.md` for detailed documentation
2. Check `.github/workflows/README.md` for workflow details
3. Look at `tests/Feature/ExampleTenantTest.php` for testing examples
4. Review PHPStan output for specific error locations

---

**Pro Tip**: Run `vendor/bin/pint && vendor/bin/phpstan analyse && vendor/bin/pest` before every commit to catch issues early! 🚀
