<?php

declare(strict_types=1);

namespace App\Settings\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidSettingKey implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[a-z][a-z0-9_.]*$/', $value)) {
            $fail(__('validation.valid_setting_key', ['value' => $value]));
        }
    }
}
