<?php

declare(strict_types=1);

namespace App\Domain\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a username does not conflict with reserved system names.
 *
 * S1 - Secure: Prevents users from impersonating system accounts.
 */
final class SystemUsername implements ValidationRule
{
    /**
     * Reserved usernames that cannot be used by regular users.
     */
    private const RESERVED = [
        'admin',
        'administrator',
        'superadmin',
        'super_admin',
        'system',
        'root',
        'support',
        'helpdesk',
        'noreply',
        'no-reply',
        'info',
        'contact',
        'api',
        'bot',
        'daemon',
        'master',
        'webmaster',
        'postmaster',
        'abuse',
        'security',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $normalized = strtolower(trim($value));

        if (in_array($normalized, self::RESERVED, true)) {
            $fail('The :attribute is a reserved system name and cannot be used.');
        }

        if (preg_match('/^[a-zA-Z0-9_]+$/', $value) !== 1) {
            $fail('The :attribute must only contain letters, numbers, and underscores.');
        }
    }
}
