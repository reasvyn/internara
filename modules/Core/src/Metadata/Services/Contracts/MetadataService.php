<?php

declare(strict_types=1);

namespace Modules\Core\Metadata\Services\Contracts;

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
     * Retrieves a metadata value by key from the application information registry.
     *
     * This acts as the technical accessor for static system data defined in
     * app_info.json, ensuring that versioning and Blueprint IDs are consistent
     * across all modules.
     *
     * @param string $key The dot-notation key (e.g., 'author.name').
     * @param mixed $default Fallback value if the key is missing.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retrieves the full application information as an associative array.
     */
    public function getAll(): array;

    /**
     * Retrieves the application's semantic version (SemVer).
     *
     * Used for asset cache busting and displaying the current readiness stage
     * (Alpha/Beta/Stable) to the end user.
     */
    public function getVersion(): string;

    /**
     * Retrieves the authoritative author information registry.
     */
    public function getAuthor(): array;

    /**
     * Retrieves the Product Identity (App Name).
     *
     * This represents the software itself (e.g., "Internara"), defined in app_info.json.
     */
    public function getAppName(): string;

    /**
     * Retrieves the Instance Identity (Brand Name).
     *
     * This represents the institution using the system (e.g., "SMKN 1 Jakarta"),
     * managed via settings with a fallback to the App Name.
     */
    public function getBrandName(): string;

    /**
     * Verifies the integrity of the application metadata and author attribution.
     *
     * This is a critical security mandate that prevents unauthorized tampering
     * with system credits and project-level metadata during runtime.
     *
     * @throws \RuntimeException If integrity violation or attribution theft is detected.
     */
    public function verifyIntegrity(): void;
}
