# Test Failures Fixed - Summary

## Date: 2026-01-26

### Issues Resolved:

#### 1. Missing Database Migrations ✅
**Problem**: Tests were failing with "relation 'admins' does not exist" and similar errors for company_users table.

**Solution**: Created proper migrations:
- `Modules/GlobalAdmin/database/migrations/2026_01_26_123348_create_admins_table.php`
  - Uses `landlord` connection
  - UUID primary key
  - Fields: name, email (unique), password, email_verified_at, remember_token, timestamps

- `Modules/CompanyManagement/database/migrations/2026_01_26_123356_create_company_users_table.php`
  - Uses default (tenant) connection
  - UUID primary key  
  - Fields: name, email (unique), password, email_verified_at, remember_token, timestamps

#### 2. Missing API Routes ✅
**Problem**: Tests expecting routes like `/api/landlord/protected` were getting 404 errors.

**Solution**: Added comprehensive authentication routes in `routes/api.php`:

**Authentication Endpoints:**
- `POST /api/auth/landlord/login` - Landlord admin login
- `POST /api/auth/tenant/login` - Tenant user login
- `POST /api/auth/login` - Regular user login
- `POST /api/auth/refresh` - Refresh JWT token
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/me` - Get current authenticated user

**Protected Endpoints:**
- `GET /api/landlord/protected` - Landlord protected route (requires auth:landlord)
- `GET /api/tenant/protected` - Tenant protected route (requires auth:tenant)
- `GET /api/tenant/check-context` - Check tenant context
- `GET /api/user/protected` - User protected route (requires auth:api)

#### 3. Unique Constraint Violations ✅
**Problem**: Parallel tests were failing with duplicate `database_name` unique constraint violations.

**Solution**: Modified `tests/TestCase.php::createAndActAsTenant()` to generate unique database names:
```php
$uniqueDatabaseName = env('TENANT_DB_DATABASE', 'sifet_test_tenant') . '_' . uniqid();
```

#### 4. Wrong Tenant Model Configuration ✅
**Problem**: `config/multitenancy.php` was using the base Spatie Tenant model instead of the custom one.

**Solution**: Updated configuration:
```php
'tenant_model' => \Modules\GlobalAdmin\Models\Tenant::class,
```

#### 5. PHPStan Covariance Errors ✅
**Problem**: Factory `$model` properties had incorrect PHPDoc types.

**Solution**: Updated both factories to use correct type:
```php
/**
 * @var class-string<ModelName>
 */
protected $model = ModelName::class;
```

### Remaining Known Issues:

1. **Tenant::current() returning null** - The TenancyByJwtTokenTest is showing that after calling `$tenant->makeCurrent()`, the `Tenant::current()` still returns null. This might be a service container binding issue that needs investigation.

2. **RefreshDatabase trait** - Need to ensure all migrations run properly when tests use RefreshDatabase. The landlord connection migrations need to be discovered and executed.

### Recommendations:

1. **Run database migrations before tests** in CI/CD:
   ```bash
   php artisan migrate --database=landlord --path=Modules/GlobalAdmin/database/migrations
   php artisan migrate --database=landlord --path=database/migrations
   ```

2. **Consider creating a test setup class** that handles:
   - Running all landlord migrations
   - Creating a test tenant database
   - Running tenant migrations

3. **Add database seeding** for test data if needed

### Next Steps:

Run the tests again to verify the fixes:
```bash
vendor/bin/pest --parallel --processes=4
```

Expected Results:
- No more "table does not exist" errors
- No more 404 route errors
- No more unique constraint violations
- Remaining failures should be related to tenant context resolution only
