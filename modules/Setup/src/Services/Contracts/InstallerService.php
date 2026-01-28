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
     * Ensures the .env file exists, creating it from .env.example if necessary.
     *
     * @return bool True if the file exists or was created, false otherwise.
     */
    public function ensureEnvFileExists(): bool;

    /**
     * Generates the application key.
     *
     * @return bool True if the key was generated successfully, false otherwise.
     */
    public function generateAppKey(): bool;

    /**
     * Validates the system environment requirements.
     *
     * @return array<string, array<string, bool|string>>
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
