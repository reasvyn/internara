<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final readonly class PasswordRules
{
    public static function default(): array
    {
        return ['required', 'string', Password::min(8)->mixedCase()->numbers()];
    }

    public static function defaultAsArray(): array
    {
        return ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'];
    }
}
