<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Models\User;
use Illuminate\Support\Str;

/**
 * Generates unique usernames for new user accounts.
 *
 * S1 - Secure: Collision-resistant generation with max iteration guard.
 * S2 - Sustain: Centralized business rule for user identification.
 */
final class UserIdentifierGenerator
{
    /**
     * Maximum number of collision attempts before giving up.
     */
    private const MAX_ATTEMPTS = 100;

    /**
     * Generate a unique username with prefix 'u' followed by alphanumeric characters.
     *
     * Example output: `ua1b2c3d4`
     *
     * @param int $length Length of the random string (excluding 'u' prefix)
     *
     * @throws \RuntimeException when a unique username cannot be generated within MAX_ATTEMPTS
     */
    public static function generateUsername(int $length = 8): string
    {
        $attempts = 0;

        do {
            $random = strtolower(Str::random($length));
            $username = 'u'.$random;
            $attempts++;

            if ($attempts >= self::MAX_ATTEMPTS) {
                throw new \RuntimeException('Unable to generate unique username after '.self::MAX_ATTEMPTS.' attempts.');
            }
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
