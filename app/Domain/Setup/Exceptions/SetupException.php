<?php

declare(strict_types=1);

namespace App\Domain\Setup\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class SetupException extends DomainException
{
    public static function auditFailed(): self
    {
        return (new self('System audit check failed.'))
            ->withHint('Review the audit output above and fix the failing checks before proceeding.');
    }

    public static function provisioningFailed(string $step): self
    {
        return (new self("Provisioning step failed: {$step}"))
            ->withHint('Check the error message above and ensure all prerequisites are met.');
    }

    public static function tokenGenerationFailed(): self
    {
        return (new self('Failed to generate setup token.'))
            ->withHint('Ensure the setups table exists and the application key is set.');
    }

    public static function invalidToken(): self
    {
        return (new self('Invalid setup token.'))
            ->withHint('Use `php artisan setup:install` to generate a new token.');
    }

    public static function tokenExpired(): self
    {
        return (new self('Setup token has expired.'))
            ->withHint('Use `php artisan setup:install` to generate a new token.');
    }

    public static function alreadyInstalled(): self
    {
        return (new self('Application is already installed.'))
            ->withHint('Use `php artisan setup:reset` to start over.');
    }
}
