<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

/**
 * Defines the contract for the service that handles the technical installation process.
 */
interface InstallerService
{
    /**
     * Orchestrates the complete installation process.
     *
     * @return bool True if the installation was successful, false otherwise.
     */
    public function install(): bool;

    /**
     * Validates the system environment requirements.
     *
     * @return array<string, bool> An associative array of requirement names and their status.
     */
    public function validateEnvironment(): array;

    /**
     * Executes the database migrations.
     *
     * @return bool True if migrations were successful, false otherwise.
     */
    public function runMigrations(): bool;

    /**
     * Executes the core and shared database seeders.
     *
     * @return bool True if seeding was successful, false otherwise.
     */
    public function runSeeders(): bool;

    /**
     * Creates the storage symbolic link.
     *
     * @return bool True if the symlink was created successfully, false otherwise.
     */
    public function createStorageSymlink(): bool;
}
