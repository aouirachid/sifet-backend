<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;

it('verifies that all module service providers are loaded', function () {
    $modulesPath = base_path('Modules');
    $providers = [];

    // Find all service provider files in modules
    foreach (glob($modulesPath.'/*/app/Providers/*ServiceProvider.php') as $file) {
        // Extract module name and provider class name
        // Example: C:\path\Modules\CompanyManagement\app\Providers\CompanyManagementServiceProvider.php
        // Should become: Modules\CompanyManagement\Providers\CompanyManagementServiceProvider

        // Get the file name without extension
        $fileName = basename($file, '.php');

        // Extract module name from path
        preg_match('/Modules[\/\\\\]([^\/\\\\]+)[\/\\\\]/', $file, $matches);
        if (isset($matches[1])) {
            $moduleName = $matches[1];
            // Build the correct namespace (without 'app' because PSR-4 maps Modules\ModuleName to app/)
            $class = "Modules\\{$moduleName}\\Providers\\{$fileName}";
            $providers[] = $class;
        }
    }

    // ✅ Assert that we found at least one provider
    expect(count($providers))->toBeGreaterThan(0, 'No module service providers found');

    // ✅ Verify each provider is loaded
    foreach ($providers as $provider) {
        expect(App::getProvider($provider))->not->toBeNull("Provider {$provider} is not loaded");
    }
});

it('verifies that package service providers are loaded in the application container', function () {
    // Get all registered service providers from the container
    $providers = app()->getLoadedProviders();

    // ✅ Assert that service providers are loaded
    expect($providers)->not->toBeEmpty();

    // ✅ Verify key application providers are registered
    $expectedProviders = [
        'Illuminate\Auth\AuthServiceProvider',
        'Illuminate\Broadcasting\BroadcastServiceProvider',
        'Illuminate\Bus\BusServiceProvider',
        'Illuminate\Cache\CacheServiceProvider',
        'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
        'Illuminate\Cookie\CookieServiceProvider',
        'Illuminate\Database\DatabaseServiceProvider',
        'Illuminate\Encryption\EncryptionServiceProvider',
        'Illuminate\Filesystem\FilesystemServiceProvider',
        'Illuminate\Foundation\Providers\FoundationServiceProvider',
        'Illuminate\Hashing\HashServiceProvider',
        'Illuminate\Mail\MailServiceProvider',
        'Illuminate\Notifications\NotificationServiceProvider',
        'Illuminate\Pagination\PaginationServiceProvider',
        'Illuminate\Pipeline\PipelineServiceProvider',
        'Illuminate\Queue\QueueServiceProvider',
        'Illuminate\Redis\RedisServiceProvider',
        'Illuminate\Auth\Passwords\PasswordResetServiceProvider',
        'Illuminate\Session\SessionServiceProvider',
        'Illuminate\Translation\TranslationServiceProvider',
        'Illuminate\Validation\ValidationServiceProvider',
        'Illuminate\View\ViewServiceProvider',
    ];

    foreach ($expectedProviders as $provider) {
        expect(isset($providers[$provider]))->toBeTrue(
            "Service provider {$provider} is not loaded in the application container"
        );
    }

    // ✅ Verify that services from providers are accessible
    expect(app('config'))->not->toBeNull();
    expect(app('db'))->not->toBeNull();
    expect(app('cache'))->not->toBeNull();
    expect(app('queue'))->not->toBeNull();
    expect(app('mail.manager'))->not->toBeNull();
});
