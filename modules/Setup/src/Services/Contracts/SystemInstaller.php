<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

/**
 * Contract for the System Installer service.
 *
 * This service handles technical initialization of the application environment.
 */
interface SystemInstaller
{
    /**
     * Orchestrates the complete installation process.
     */
    public function install(): bool;

    /**
     * Ensures the .env file exists, creating it from .env.example if necessary.
     */
    public function ensureEnvFileExists(): bool;

    /**
     * Generates the application key if not set.
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
     */
    public function runMigrations(bool $force = false): bool;

    /**
     * Executes the foundational database seeders.
     */
    public function runSeeders(): bool;

    /**
     * Creates the storage symbolic link.
     */
    public function createStorageSymlink(): bool;
}
