<?php

declare(strict_types=1);

namespace Modules\Setup\Domain\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Value Object representing a secure setup token.
 *
 * [S1 - Secure] Implements immutable token logic with TTL and hash validation.
 */
final readonly class SetupToken
{
    private function __construct(public string $value, public ?\DateTimeImmutable $expiresAt = null)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Setup token value cannot be empty.');
        }
    }

    /**
     * Creates a new random setup token.
     */
    public static function generate(int $ttlHours = 24): self
    {
        return new self(
            value: Str::random(64),
            expiresAt: new \DateTimeImmutable()->modify("+{$ttlHours} hours"),
        );
    }

    /**
     * Reconstitutes a token from stored data.
     */
    public static function fromRaw(string $value, ?string $expiresAt = null): self
    {
        return new self(
            value: $value,
            expiresAt: $expiresAt ? new \DateTimeImmutable($expiresAt) : null,
        );
    }

    /**
     * Validates a provided token against this one.
     */
    public function matches(string $providedToken): bool
    {
        return hash_equals($this->value, $providedToken);
    }

    /**
     * Checks if the token has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return new \DateTimeImmutable() > $this->expiresAt;
    }

    /**
     * Returns the string representation.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
