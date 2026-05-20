<?php

declare(strict_types=1);

namespace App\Domain\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SystemUsername implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[a-z][a-z0-9]{2,29}$/', $value)) {
            $fail('The :attribute must be a lowercase alphanumeric string starting with a letter, between 3 and 30 characters.');
        }
    }
}
