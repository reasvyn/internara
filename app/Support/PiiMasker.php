<?php

declare(strict_types=1);

namespace App\Support;

final class PiiMasker
{
    private const MASKED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'old_password',
        'secret',
        'token',
        'api_key',
        'api_token',
        'access_token',
        'refresh_token',
        'authorization',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'national_id',
    ];

    private const PARTIAL_MASK_KEYS = [
        'email' => 'maskEmail',
        'phone' => 'maskPhone',
        'name' => 'maskName',
    ];

    public static function maskArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);

            if (self::isFullyMasked($normalized)) {
                $result[$key] = '***';

                continue;
            }

            if (self::isPartiallyMasked($normalized)) {
                $method = self::PARTIAL_MASK_KEYS[$normalized];

                $result[$key] = self::{$method}((string) $value);

                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::maskArray($value);

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    public static function maskValue(string $key, mixed $value): mixed
    {
        $normalized = strtolower($key);

        if (self::isFullyMasked($normalized)) {
            return '***';
        }

        if (self::isPartiallyMasked($normalized)) {
            $method = self::PARTIAL_MASK_KEYS[$normalized];

            return self::{$method}((string) $value);
        }

        return $value;
    }

    private static function isFullyMasked(string $key): bool
    {
        foreach (self::MASKED_KEYS as $masked) {
            if (str_contains($key, $masked)) {
                return true;
            }
        }

        return false;
    }

    private static function isPartiallyMasked(string $key): bool
    {
        return isset(self::PARTIAL_MASK_KEYS[$key]);
    }

    private static function maskEmail(string $value): string
    {
        if (! str_contains($value, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $value, 2);

        if (strlen($local) <= 2) {
            return substr($local, 0, 1).'***@'.$domain;
        }

        return substr($local, 0, 2).'***@'.$domain;
    }

    private static function maskPhone(string $value): string
    {
        $length = strlen($value);

        if ($length <= 4) {
            return '***';
        }

        return str_repeat('*', $length - 4).substr($value, -4);
    }

    private static function maskName(string $value): string
    {
        $parts = explode(' ', trim($value));

        if (count($parts) === 1) {
            return substr($value, 0, 1).str_repeat('*', max(0, strlen($value) - 1));
        }

        $first = $parts[0];
        $last = end($parts);

        return substr($first, 0, 1).'. '.$last;
    }
}
