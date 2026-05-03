<?php

declare(strict_types=1);

namespace Modules\User\Support;

use Modules\User\Models\User;

final class UsernameGenerator
{
    /**
     * Generate a unique username with a configurable prefix and random digit length.
     *
     *
     * @throws \RuntimeException
     */
    public static function generate(string $prefix = 'u', int $length = 8): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $randomNumbers = '';
            for ($i = 0; $i < $length; $i++) {
                $randomNumbers .= random_int(0, 9);
            }

            $username = $prefix.$randomNumbers;
            $attempt++;

            if ($attempt > $maxAttempts) {
                throw new \RuntimeException(
                    "Unable to generate a unique username after {$maxAttempts} attempts.",
                );
            }
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
