<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Shared\Support\Masker;

/**
 * Value Object representing authentication credentials.
 *
 * [S1 - Secure] Encapsulates credential sanitization and masking.
 */
final readonly class AuthCredentials
{
    private function __construct(
        public string $identifier,
        public string $password,
        public string $loginField,
    ) {
        if (empty($identifier)) {
            throw new InvalidArgumentException('Authentication identifier cannot be empty.');
        }

        if (empty($password)) {
            throw new InvalidArgumentException('Authentication password cannot be empty.');
        }
    }

    /**
     * Creates a new AuthCredentials instance from raw input.
     */
    public static function fromRaw(string $identifier, string $password): self
    {
        // Trim whitespace from identifier but NOT password
        $identifier = trim($identifier);

        $loginField = Str::contains($identifier, '@') ? 'email' : 'username';

        return new self(identifier: $identifier, password: $password, loginField: $loginField);
    }

    /**
     * Returns the array format expected by Laravel Auth facade.
     */
    public function toAuthArray(): array
    {
        return [
            $this->loginField => $this->identifier,
            'password' => $this->password,
        ];
    }

    /**
     * Returns a safely masked version of the identifier for logging.
     *
     * [S1 - Secure] Protects PII from being exposed in audit trails.
     */
    public function getMaskedIdentifier(): string
    {
        if ($this->loginField === 'email') {
            return Masker::email($this->identifier);
        }

        return Masker::sensitive($this->identifier);
    }
}
