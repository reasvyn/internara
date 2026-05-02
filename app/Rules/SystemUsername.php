<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a username follows the system standard:
 * Starts with 'u' followed by at least 8 alphanumeric characters.
 *
 * S1 - Secure: Enforces strict identifier patterns at the server level.
 */
class SystemUsername implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('validation.string')->translate(['attribute' => $attribute]);

            return;
        }

        // Pattern: starts with 'u', followed by 8 or more alphanumeric characters
        // ^u[a-z0-9]{8,}$ (case insensitive handled by modifier if needed)
        if (! preg_match('/^u[a-zA-Z0-9]{8,}$/', $value)) {
            $fail(__('validation.custom.username_format'));
        }
    }
}
