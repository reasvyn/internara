<?php

declare(strict_types=1);

namespace App\Core\Support;

use Illuminate\Validation\Rules\Password;

final readonly class PasswordRules
{
    public static function default(): array
    {
        return ['required', 'string', Password::min(8)->mixedCase()->numbers()];
    }
}
