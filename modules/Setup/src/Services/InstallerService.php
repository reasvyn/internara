<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Modules\Setup\Services\Contracts\InstallerService as InstallerServiceContract;

/**
 * Service implementation for handling the technical installation process.
 */
class InstallerService implements InstallerServiceContract
{
    /**
     * Orchestrates the complete installation process.
     */
    public function install(): bool
    {
        $requirements = $this->validateEnvironment();

        if (in_array(false, $requirements, true)) {
            return false;
        }

        if (! $this->runMigrations()) {
            return false;
        }

        if (! $this->runSeeders()) {
            return false;
        }

        return $this->createStorageSymlink();
    }

    /**
     * Validates the system environment requirements.
     */
    public function validateEnvironment(): array
    {
        return [
            'php_version' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'env_exists' => File::exists(base_path('.env')),
            'writable_storage' => is_writable(storage_path()),
            'writable_bootstrap' => is_writable(base_path('bootstrap/cache')),
        ];
    }

    /**
     * Executes the database migrations.
     */
    public function runMigrations(): bool
    {
        try {
            return Artisan::call('migrate', ['--force' => true]) === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Executes the core and shared database seeders.
     */
    public function runSeeders(): bool
    {
        try {
            return Artisan::call('db:seed', ['--force' => true]) === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Creates the storage symbolic link.
     */
    public function createStorageSymlink(): bool
    {
        try {
            if (File::exists(public_path('storage'))) {
                return true;
            }

            return Artisan::call('storage:link') === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
