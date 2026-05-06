<?php

declare(strict_types=1);

namespace App\Domain\User\Data;

/**
 * Data transfer object for user creation.
 */
final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $username = null,
        public ?string $password = null,
        public bool $setupRequired = false,
        public array $profileData = [],
        public array $roles = [],
    ) {}

    /**
     * Create a DTO from raw arrays.
     *
     * @param array<string, mixed> $userData
     * @param array<string, mixed> $profileData
     * @param list<string> $roles
     */
    public static function fromArray(
        array $userData,
        array $profileData = [],
        array $roles = [],
    ): self {
        return new self(
            name: $userData['name'],
            email: $userData['email'],
            username: $userData['username'] ?? null,
            password: $userData['password'] ?? null,
            setupRequired: $userData['setup_required'] ?? false,
            profileData: $profileData,
            roles: $roles,
        );
    }

    /**
     * Get the resolved username, falling back to email-based generation.
     */
    public function resolvedUsername(): string
    {
        return $this->username ?? str($this->email)->before('@')->slug()->toString();
    }

    /**
     * Determine if a password needs to be auto-generated.
     */
    public function requiresPasswordGeneration(): bool
    {
        return $this->password === null;
    }
}
