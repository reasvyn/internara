<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

/**
 * Base exception for all authentication-related errors.
 *
 * Provides context-aware error messages and supports recovery hints
 * for both CLI and web environments. Designed to prevent information
 * leakage (e.g., whether email exists) while still giving actionable
 * feedback.
 */
class AuthException extends RuntimeException
{
    /**
     * @var array<string, mixed> Additional context for debugging
     */
    protected array $context = [];

    /**
     * @var string|null Hint for resolving the error
     */
    protected ?string $hint = null;

    public static function invalidCredentials(): self
    {
        $exception = new self(__('auth.failed'));
        $exception->setHint('Check your email/username and password. Both are case-sensitive.');

        return $exception;
    }

    public static function accountSuspended(): self
    {
        $exception = new self(__('auth.blocked'));
        $exception->setHint('Your account has been suspended. Contact an administrator for assistance.');

        return $exception;
    }

    public static function accountArchived(): self
    {
        $exception = new self(__('auth.blocked'));
        $exception->setHint('Your account has been archived. Contact an administrator for assistance.');

        return $exception;
    }

    public static function accountInactive(): self
    {
        $exception = new self(__('auth.blocked'));
        $exception->setHint('Your account is not yet active. Verify your email or contact support.');

        return $exception;
    }

    public static function accountStatus(string $status): self
    {
        $exception = new self(__('auth.blocked'));
        $exception->setHint("Your account status is '{$status}'. Contact an administrator.");
        $exception->setContext(['status' => $status]);

        return $exception;
    }

    public static function passwordMismatch(): self
    {
        $exception = new self(__('auth.password_mismatch'));
        $exception->setHint('The current password you entered does not match our records.');

        return $exception;
    }

    public static function resetTokenInvalid(): self
    {
        $exception = new self(__('passwords.token'));
        $exception->setHint('This password reset link has expired. Request a new one.');

        return $exception;
    }

    public static function resetThrottled(int $seconds): self
    {
        $exception = new self(__('auth.throttle', ['seconds' => $seconds]));
        $exception->setHint('Too many reset attempts. Please wait before trying again.');
        $exception->setContext(['retry_after' => $seconds]);

        return $exception;
    }

    public static function loginThrottled(int $seconds): self
    {
        $exception = new self(__('auth.throttle', ['seconds' => $seconds]));
        $exception->setHint('Too many login attempts. Please wait before trying again.');
        $exception->setContext(['retry_after' => $seconds]);

        return $exception;
    }

    public static function userNotFound(string $email): self
    {
        $exception = new self(__('passwords.user'));
        $exception->setHint('No account exists with that email address.');
        $exception->setContext(['email' => $email]);

        return $exception;
    }

    public static function cannotDeleteSelf(): self
    {
        $exception = new self('You cannot delete your own account.');
        $exception->setHint('Ask another administrator to delete your account.');

        return $exception;
    }

    public static function cannotDeleteLastAdmin(): self
    {
        $exception = new self('Cannot delete the last administrator account.');
        $exception->setHint('Assign administrator role to another user before deleting this account.');

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
