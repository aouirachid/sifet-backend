<?php

declare(strict_types=1);

namespace Modules\GlobalAdmin\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class SafeMigrateCommand extends Command
{
    use TenantAware;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate {--tenant=* : The ID of the tenant(s) to migrate} {--fresh : Whether to run migrate:fresh} {--seed : Whether to seed the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely migrate tenant databases with transaction support. Note: Transactional DDL is required for full rollback safety (e.g. PostgreSQL).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::current();

        if (! $tenant) {
            $this->error('No current tenant detected.');

            return Command::FAILURE;
        }

        $connectionName = config('multitenancy.tenant_database_connection_name', 'tenant');
        $driver = DB::connection($connectionName)->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $this->warn("Warning: The '{$driver}' driver does not support transactional DDL.");
            $this->warn('Schema changes (CREATE, ALTER, DROP) will not be rolled back on failure.');

            if (! $this->confirm('Do you wish to continue?', true)) {
                return Command::FAILURE;
            }
        }

        $this->info("Starting safe migration for tenant: {$tenant->id} ({$tenant->getDatabaseName()})");
        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        DB::connection($connectionName)->beginTransaction();

        try {
            $this->info("Running {$command}...");

            $exitCode = Artisan::call($command, [
                '--database' => $connectionName,
                '--force' => true,
                '--seed' => $this->option('seed'),
            ], $this->output);

            if ($exitCode !== Command::SUCCESS) {
                throw new \RuntimeException("Migration command failed with exit code: {$exitCode}");
            }

            DB::connection($connectionName)->commit();
            $this->info("Successfully migrated and committed tenant: {$tenant->id}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::connection($connectionName)->rollBack();

            Log::error("Safe migration failed for tenant {$tenant->id}: ".$e->getMessage(), [
                'tenant_id' => $tenant->id,
                'exception' => $e,
            ]);

            $this->error("Failed to migrate tenant: {$tenant->id}. Transaction rolled back.");
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
