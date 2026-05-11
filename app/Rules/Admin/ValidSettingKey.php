<?php

declare(strict_types=1);

namespace App\Rules\Admin;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class ValidSettingKey implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[a-z][a-z0-9_.]*$/', $value)) {
            $fail("The setting key must be lowercase alphanumeric with underscores or dots. Got: {$value}");
        }
    }
}
