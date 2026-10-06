<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use InvalidArgumentException;

/**
 * Reusable random token generator (Support: static, pure PHP, no side effects).
 */
final class Token
{
    /**
     * Charset for human-transcribable codes: uppercase letters and digits only.
     * Characters must be unique so every position keeps an uniform distribution.
     */
    public const UPPERCASE_ALNUM = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const DEFAULT_LENGTH = 6;

    /**
     * Mint a cryptographically random token drawn from the given charset.
     *
     * @throws InvalidArgumentException when length is below 1 or charset is empty
     */
    public static function generate(
        int $length = self::DEFAULT_LENGTH,
        string $charset = self::UPPERCASE_ALNUM,
    ): string {
        if ($length < 1) {
            throw new InvalidArgumentException('Token length must be at least 1.');
        }

        if ($charset === '') {
            throw new InvalidArgumentException('Token charset must not be empty.');
        }

        $lastIndex = strlen($charset) - 1;
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= $charset[random_int(0, $lastIndex)];
        }

        return $token;
    }
}
