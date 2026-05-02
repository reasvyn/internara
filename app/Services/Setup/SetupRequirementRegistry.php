<?php

declare(strict_types=1);

namespace App\Services\Setup;

/**
 * Registry for system requirements to be checked during setup.
 *
 * S3 - Scalable: Allows adding new requirements dynamically.
 */
class SetupRequirementRegistry
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $extensions = [
        'bcmath', 'ctype', 'fileinfo', 'mbstring', 'openssl',
        'pdo', 'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip',
    ];

    /**
     * @var array<int, string>
     */
    private array $recommendedExtensions = ['redis', 'pcntl', 'posix'];

    /**
     * @var array<int, string>
     */
    private array $writableDirs = ['storage', 'bootstrap/cache', 'database'];

    /**
     * Register a new required extension.
     */
    public function requireExtension(string $extension): void
    {
        if (!in_array($extension, $this->extensions)) {
            $this->extensions[] = $extension;
        }
    }

    /**
     * Register a new recommended extension.
     */
    public function recommendExtension(string $extension): void
    {
        if (!in_array($extension, $this->recommendedExtensions)) {
            $this->recommendedExtensions[] = $extension;
        }
    }

    /**
     * Register a new writable directory check.
     */
    public function requireWritableDir(string $path): void
    {
        if (!in_array($path, $this->writableDirs)) {
            $this->writableDirs[] = $path;
        }
    }

    /**
     * Get all required extensions.
     *
     * @return array<int, string>
     */
    public function getRequiredExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Get all recommended extensions.
     *
     * @return array<int, string>
     */
    public function getRecommendedExtensions(): array
    {
        return $this->recommendedExtensions;
    }

    /**
     * Get all required writable directories.
     *
     * @return array<int, string>
     */
    public function getWritableDirs(): array
    {
        return $this->writableDirs;
    }
}
