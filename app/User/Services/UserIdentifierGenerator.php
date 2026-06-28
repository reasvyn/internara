<?php

declare(strict_types=1);

namespace App\User\Services;

use App\User\Models\User;

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
     * @param string $email Email address to base the username on
     *
     * @throws \RuntimeException when a unique username cannot be generated within MAX_ATTEMPTS
     */
    public static function generateUsername(string $email): string
    {
        $local = explode('@', $email)[0] ?? '';
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $local));

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;
        $attempts = 0;

        while (User::where('username', $username)->exists()) {
            $username = $base.$counter;
            $counter++;
            $attempts++;

            if ($attempts >= self::MAX_ATTEMPTS) {
                throw new \RuntimeException(
                    'Unable to generate unique username after '.self::MAX_ATTEMPTS.' attempts.',
                );
            }
        }

        return $username;
    }
}
