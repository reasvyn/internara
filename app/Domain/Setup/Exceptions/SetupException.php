<?php

declare(strict_types=1);

namespace App\Domain\Setup\Exceptions;

use RuntimeException;

/**
 * Base exception for all setup-related errors.
 *
 * Provides context-aware error messages and supports recovery hints
 * for both CLI and web environments.
 */
class SetupException extends RuntimeException
{
    /**
     * @var array<string, mixed> Additional context for debugging
     */
    protected array $context = [];

    /**
     * @var string|null Hint for resolving the error
     */
    protected ?string $hint = null;

    public static function missingEnvExample(): self
    {
        $exception = new self('Cannot create .env file: .env.example template is missing.');
        $exception->setHint('Copy .env.example to .env manually or restore it from your deployment source.');
        $exception->setContext(['expected_path' => base_path('.env.example')]);

        return $exception;
    }

    public static function keyGenerationFailed(): self
    {
        $exception = new self('Failed to generate application key.');
        $exception->setHint('Ensure the .env file exists and is writable, then retry.');

        return $exception;
    }

    public static function migrationFailed(string $command, ?\Throwable $previous = null): self
    {
        $exception = new self(
            "Database migration failed: {$command}. Check your database connection and credentials.",
            previous: $previous,
        );
        $exception->setHint('Verify DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, and DB_PASSWORD in your .env file.');

        return $exception;
    }

    public static function seedingFailed(?\Throwable $previous = null): self
    {
        $exception = new self(
            'Database seeding failed. Check seeder classes and database state.',
            previous: $previous,
        );
        $exception->setHint('Run `php artisan db:seed` manually to see detailed error output.');

        return $exception;
    }

    public static function settingsConfigurationFailed(?\Throwable $previous = null): self
    {
        $exception = new self(
            'Failed to configure initial system settings.',
            previous: $previous,
        );
        $exception->setHint('This may indicate a database issue or corrupted migrations. Check the logs for details.');

        return $exception;
    }

    public static function storageLinkFailed(): self
    {
        $exception = new self('Failed to create storage link. Check directory permissions.');
        $exception->setHint('Ensure public/storage does not exist as a file and storage/app/public is writable.');

        return $exception;
    }

    public static function cacheClearFailed(string $cacheType): self
    {
        $exception = new self("Failed to clear {$cacheType} cache.");
        $exception->setHint("Manually run `php artisan {$cacheType}:clear` to diagnose the issue.");

        return $exception;
    }

    public static function schoolAlreadyExists(): self
    {
        $exception = new self('School profile already exists and cannot be created again.');
        $exception->setHint('Each installation supports only one school. Contact support for multi-school requirements.');

        return $exception;
    }

    public static function wizardAlreadyCompleted(): self
    {
        $exception = new self('Setup wizard has already been completed.');
        $exception->setHint('To reinstall, run `php artisan setup:reset` first.');

        return $exception;
    }

    public function setHint(string $hint): self
    {
        $this->hint = $hint;

        return $this;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Format exception for CLI display with optional hint.
     */
    public function toCliOutput(): string
    {
        $output = $this->getMessage();

        if ($this->hint !== null) {
            $output .= "\n\nHint: {$this->hint}";
        }

        return $output;
    }
}
