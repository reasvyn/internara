<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Generates unique identifiers for system users.
 *
 * S1 - Secure: Timing-safe and collision-resistant generation.
 * S2 - Sustain: Centralized business rule for user identification.
 */
class UserIdentifierGenerator
{
    /**
     * Generate a unique username with prefix 'u' followed by 8+ alphanumeric characters.
     * Example: uA1b2C3d4
     */
    public static function generateUsername(int $length = 8): string
    {
        do {
            // Generate a random alphanumeric string and convert to lowercase for better UX
            $random = strtolower(Str::random($length));
            $username = 'u'.$random;
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
