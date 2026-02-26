<?php

declare(strict_types=1);

namespace Modules\Setup\Contracts;

/**
 * Standard interface for modules requiring ordered system initialization.
 *
 * Modules implementing this contract can define seeding, default configurations,
 * or environment setups that must execute in a specific sequence during
 * the 'php artisan app:setup' process.
 */
interface Initializable
{
    /**
     * Execute the initialization logic for the module.
     *
     * @return bool True if initialization was successful.
     */
    public function initialize(): bool;

    /**
     * Get the initialization priority.
     *
     * Lower values execute earlier (e.g., Core/Auth should be lower than Internship).
     */
    public function getPriority(): int;

    /**
     * Get a description of the initialization task for the console output.
     */
    public function getDescription(): string;
}
