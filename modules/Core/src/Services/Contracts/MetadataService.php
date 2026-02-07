<?php

declare(strict_types=1);

namespace Modules\Core\Services\Contracts;

/**
 * Defines the contract for application metadata management.
 *
 * This service acts as the Single Source of Truth (SSoT) for technical identity,
 * versioning, and author attribution, enforcing architectural integrity.
 */
interface MetadataService
{
    /**
     * The authoritative author identity for integrity verification.
     */
    public const AUTHOR_IDENTITY = 'Reas Vyn';

    /**
     * Retrieves a metadata value by key from the application information.
     *
     * @param string $key The dot-notation key.
     * @param mixed $default Fallback value if the key is missing.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retrieves the full application information as an array.
     */
    public function getAll(): array;

    /**
     * Retrieves the application's semantic version.
     */
    public function getVersion(): string;

    /**
     * Retrieves the application's series code.
     */
    public function getSeriesCode(): string;

    /**
     * Retrieves the authoritative author information.
     */
    public function getAuthor(): array;

    /**
     * Verifies the integrity of the application metadata and author attribution.
     *
     * @throws \RuntimeException If integrity violation is detected.
     */
    public function verifyIntegrity(): void;
}
