<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

<h1 align="center">Sifet Backend</h1>

<p align="center">
  A multi-tenant, modular Laravel 12 backend application for fleet, store, and order management.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-12.0-red" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PostgreSQL-16-blue" alt="PostgreSQL 16">
  <img src="https://img.shields.io/badge/Redis-7-red" alt="Redis 7">
  <img src="https://img.shields.io/badge/License-MIT-green" alt="MIT License">
</p>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Architecture](#-architecture)
- [Tech Stack](#-tech-stack)
- [Modules](#-modules)
- [Installation](#-installation)
- [Development](#-development)
- [Testing](#-testing)
- [CI/CD](#-cicd)
- [Code Quality](#-code-quality)
- [License](#-license)

---

## 🎯 Overview

Sifet Backend is a robust, multi-tenant SaaS backend designed for managing companies, fleets, stores, orders, and billing. Built with Laravel 12, it features:

- **Multi-Tenancy**: Database-per-tenant isolation using Spatie Multitenancy
- **Modular Architecture**: Domain-driven modules using nwidart/laravel-modules
- **API-First**: JWT-based authentication for secure API access
- **Scalable**: PostgreSQL + PostGIS for geospatial data, Redis for caching

---

## 🏗 Architecture

### Multi-Tenant Strategy

```
┌─────────────────────────────────────────────────────────┐
│                      Request                            │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│              DomainTenantFinder                         │
│         (Resolves tenant by request host)               │
└─────────────────────┬───────────────────────────────────┘
                      │
          ┌───────────┴───────────┐
          │                       │
          ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│  Landlord DB    │     │   Tenant DB     │
│  (PostgreSQL)   │     │  (PostgreSQL)   │
├─────────────────┤     ├─────────────────┤
│ • Tenants       │     │ • Module Data   │
│ • Domains       │     │ • Users         │
│ • Global Config │     │ • Business Data │
└─────────────────┘     └─────────────────┘
```

### Project Structure

```
sifet-backend/
├── app/
│   ├── Models/              # Core models (User)
│   ├── Providers/           # Service providers
│   └── TenantFinder/        # Tenant resolution logic
├── Modules/                 # Domain modules
│   ├── GlobalAdmin/         # Landlord administration
│   ├── CompanyManagement/   # Company management
│   ├── FinanceBilling/      # Finance & billing
│   ├── FleetManagement/     # Fleet management
│   ├── OrderProcessing/     # Order management
│   └── StoreManagement/     # Store management
├── config/
│   ├── multitenancy.php     # Multi-tenant config
│   ├── database.php         # DB connections (landlord/tenant)
│   └── modules.php          # Modules config
├── database/
│   └── migrations/          # Core migrations
├── tests/                   # Application tests
└── .github/workflows/       # CI pipeline
```

---

## 🛠 Tech Stack

| Category | Technology |
|----------|------------|
| **Framework** | Laravel 12.0 |
| **PHP Version** | 8.2+ |
| **Database** | PostgreSQL 16 + PostGIS 3.4 |
| **Cache** | Redis 7 |
| **Authentication** | JWT (php-open-source-saver/jwt-auth) |
| **Multi-Tenancy** | Spatie Laravel Multitenancy 4.0 |
| **Modular Architecture** | nwidart/laravel-modules 12.0 |
| **Testing** | Pest 3.8 (Parallel execution) |
| **Static Analysis** | PHPStan + Larastan (Level 3) |
| **Code Style** | Laravel Pint |
| **Debugging** | Laravel Telescope |

---

## 📦 Modules

All modules follow a consistent structure with their own Controllers, Models, Services, Routes, and Tests.

| Module | Description | Database |
|--------|-------------|----------|
| **GlobalAdmin** | Tenant & domain management, global settings | Landlord |
| **CompanyManagement** | Company profiles, settings | Tenant |
| **FinanceBilling** | Invoicing, payments, subscriptions | Tenant |
| **FleetManagement** | Vehicles, drivers, tracking | Tenant |
| **OrderProcessing** | Orders, fulfillment, status management | Tenant |
| **StoreManagement** | Store locations, inventory | Tenant |

### Module Structure

```
Modules/{ModuleName}/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Providers/
│   ├── Services/
│   └── ...
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
├── module.json
└── composer.json
```

---

## 🚀 Installation

### Prerequisites

- PHP 8.2+
- Composer 2.x
- PostgreSQL 16+ with PostGIS extension
- Redis 7+
- Node.js 18+ (for frontend assets)

### Setup

```bash
# Clone the repository
git clone <repository-url>
cd sifet-backend

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Create databases (PostgreSQL)
# Create: sifet_landlord (landlord database)
# Create: sifet_tenant_* (tenant databases)

# Run migrations
php artisan migrate --database=landlord
php artisan module:migrate GlobalAdmin --database=landlord

# Build assets
npm run build
```

### Quick Setup (Alternative)

```bash
composer setup
```

---

## 💻 Development

### Start Development Server

```bash
# Run all services concurrently
composer dev

# This starts:
# - Laravel server (php artisan serve)
# - Queue worker (php artisan queue:listen)
# - Log viewer (php artisan pail)
# - Vite dev server (npm run dev)
```

### Useful Commands

```bash
# Create a new module
php artisan module:make ModuleName

# Run module migrations
php artisan module:migrate ModuleName

# Generate module components
php artisan module:make-controller ControllerName ModuleName
php artisan module:make-model ModelName ModuleName
php artisan module:make-migration migration_name ModuleName
```

---

## 🧪 Testing

### Run All Tests

```bash
# Run all tests
vendor/bin/pest

# Run tests in parallel (recommended)
vendor/bin/pest --parallel --processes=4

# Run specific module tests
vendor/bin/pest Modules/CompanyManagement/tests
vendor/bin/pest Modules/GlobalAdmin/tests
```

### Test Configuration

Tests use separate databases:
- **Landlord**: `sifet_test_landlord`
- **Tenant**: `sifet_test_tenant`

### Writing Multi-Tenant Tests

```php
use Tests\TestCase;

class MyTest extends TestCase
{
    public function test_tenant_operation(): void
    {
        // Create and activate a test tenant
        $tenant = $this->createAndActAsTenant([
            'data' => ['name' => 'Test Company']
        ]);
        
        // Now in tenant context...
    }
}
```

---

## 🔄 CI/CD

The project uses GitHub Actions for continuous integration.

### Pipeline Stages

1. **🎨 Code Formatting** - Laravel Pint
2. **🔍 Static Analysis** - PHPStan Level 3
3. **🔒 Security Audit** - Composer audit
4. **✅ Tests** - Pest (parallel execution)

### Workflow Triggers

- Push to any branch
- Pull requests to `main` or `develop`

### CI Services

- PostgreSQL 16 + PostGIS 3.4
- Redis 7

---

## 📏 Code Quality

### Code Formatting

```bash
# Check formatting
vendor/bin/pint --test

# Auto-fix formatting
vendor/bin/pint
```

### Static Analysis

```bash
# Run PHPStan
vendor/bin/phpstan analyse --memory-limit=2G

# Clear cache if needed
vendor/bin/phpstan clear-result-cache
```

### Security Audit

```bash
composer audit
```

### Pre-Commit Checklist

```bash
# Run before every commit
vendor/bin/pint && vendor/bin/phpstan analyse && vendor/bin/pest
```

---

## 📚 Documentation

- [CI Quick Reference](CI_QUICK_REFERENCE.md) - Quick commands for CI/CD
- [Multi-Tenant CI Setup](MULTI_TENANT_CI_SETUP.md) - Detailed multi-tenant testing guide

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

<p align="center">
  Built with ❤️ using Laravel
</p>
