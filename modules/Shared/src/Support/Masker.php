<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Utility class for masking sensitive information (PII).
 */
final class Masker
{
    /**
     * Mask an email address.
     *
     * Example: user@example.com -> u***@example.com
     */
    public static function email(?string $email): string
    {
        if (empty($email)) {
            return '';
        }

        return (string) preg_replace('/(?<=.{1}).(?=.*@)/', '*', $email);
    }

    /**
     * Mask a string by keeping only a few characters.
     * Example: 08123456789 -> 081******89
     */
    public static function sensitive(string $value, int $keepStart = 3, int $keepEnd = 2): string
    {
        $length = strlen($value);

        if ($length <= $keepStart + $keepEnd) {
            return str_repeat('*', $length);
        }

        $start = substr($value, 0, $keepStart);
        $end = substr($value, -$keepEnd);
        $mask = str_repeat('*', $length - $keepStart - $keepEnd);

        return $start.$mask.$end;
    }

    /**
     * Recursively mask sensitive keys in an array.
     */
    public static function maskArray(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'api_key',
            'national_identifier',
            'nip',
            'phone',
            'email',
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::maskArray($value);

                continue;
            }

            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys)) {
                if (strtolower($key) === 'email') {
                    $data[$key] = self::email((string) $value);
                } else {
                    $data[$key] = self::sensitive((string) $value);
                }
            }
        }

        return $data;
    }
}
