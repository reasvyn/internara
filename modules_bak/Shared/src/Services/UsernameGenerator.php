<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Str;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Advanced Username Generator Service.
 *
 * Provides intelligent username generation based on user roles and identity data
 * while ensuring uniqueness across the system.
 */
class UsernameGenerator
{
    /**
     * Role-based prefixes for structured identity management.
     */
    protected const PREFIXES = [
        'super-admin' => 'u',
        'admin' => 'adm',
        'student' => 'std',
        'mentor' => 'mnt',
        'coordinator' => 'crd',
    ];

    /**
     * Generate a unique, role-based username.
     *
     * @param string $source The source string (usually email or full name).
     * @param string|null $role The user role to determine prefix.
     */
    public function generate(string $source, ?string $role = null): string
    {
        // [S1 - Secure] Specialized Generation for SuperAdmin to prevent pattern prediction
        if ($role === 'super-admin') {
            return $this->generateRandomized('u', 9);
        }

        // 1. Extract base name from email or clean up full name
        $base = Str::before($source, '@');
        $base = Str::slug($base, '');
        $base = strtolower($base);

        // 2. Apply role-based prefix if defined
        $prefix = $this->getPrefixForRole($role);
        $username = $prefix ? $prefix . '_' . $base : $base;

        // 3. Limit length according to enterprise standards
        $maxLen = (int) config('user.security.username.max_length', 30);
        if (strlen($username) > $maxLen) {
            $username = substr($username, 0, $maxLen);
        }

        // 4. Ensure uniqueness in database
        return $this->makeUnique($username);
    }

    /**
     * Generate a truly random and unique username following a specific pattern.
     *
     * @param string $prefix The starting character(s).
     * @param int $totalLength The minimum total length.
     */
    protected function generateRandomized(string $prefix, int $totalLength): string
    {
        $attempts = 0;
        $maxAttempts = 100;

        do {
            $randomLen = max($totalLength - strlen($prefix), 8);
            // Use alphanumeric characters (X) as requested
            $random = Str::lower(Str::random($randomLen));
            $username = $prefix . $random;
            $attempts++;

            if ($attempts >= $maxAttempts) {
                // Fallback to appending timestamp if collision is highly unlikely but theoretically possible
                $username .= time();
            }
        } while (User::where('username', $username)->exists());

        return $username;
    }

    /**
     * Get the designated prefix for a given role.
     */
    protected function getPrefixForRole(?string $role): ?string
    {
        if (!$role) {
            return null;
        }

        return self::PREFIXES[$role] ?? null;
    }

    /**
     * Appends a numerical suffix if the username already exists in the database.
     */
    protected function makeUnique(string $username): string
    {
        $original = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $suffix = (string) $counter;
            $maxLen = (int) config('user.security.username.max_length', 30);

            // Adjust base to fit the suffix within max length
            $baseLimit = $maxLen - strlen($suffix) - 1;
            $username = substr($original, 0, $baseLimit) . $suffix;

            $counter++;
        }

        return $username;
    }
}
