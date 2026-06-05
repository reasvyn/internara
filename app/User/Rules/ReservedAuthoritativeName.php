<?php

declare(strict_types=1);

namespace App\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ReservedAuthoritativeName implements ValidationRule
{
    private const RESERVED = [
        'admin',
        'administrator',
        'superadmin',
        'superadministrator',
        'super_admin',
        'root',
        'sysadmin',
        'system',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $lower = strtolower(trim($value));

        if (in_array($lower, self::RESERVED, true)) {
            $fail(__('user.validation.authoritative_reserved', ['value' => $value]));
        }
    }
}
