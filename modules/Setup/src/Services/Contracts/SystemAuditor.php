<?php

declare(strict_types=1);

namespace Modules\Setup\Services\Contracts;

/**
 * Contract for the System Auditor service.
 *
 * This service is responsible for performing pre-flight checks on the server environment
 * to ensure all requirements (permissions, extensions, database) are met before installation.
 */
interface SystemAuditor
{
    /**
     * Performs a full system audit.
     *
     * @return array<string, array<string, bool|string>>
     */
    public function audit(): array;

    /**
     * Checks if the required PHP version and extensions are installed.
     *
     * @return array<string, bool>
     */
    public function checkRequirements(): array;

    /**
     * Checks if the necessary directories are writable.
     *
     * @return array<string, bool>
     */
    public function checkPermissions(): array;

    /**
     * Checks if the database connection can be established.
     *
     * @return array<string, bool|string>
     */
    public function checkDatabase(): array;

    /**
     * Determines if the system passes all critical audits.
     */
    public function passes(): bool;
}
