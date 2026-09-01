<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Illuminate\Validation\Rules\Password;

final readonly class PasswordRules
{
    private const DEFAULT_MIN_LENGTH = 8;

    public static function default(int $minLength = self::DEFAULT_MIN_LENGTH): array
    {
        return ['required', 'string', Password::min($minLength)->mixedCase()->numbers()];
    }
}
