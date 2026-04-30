<?php

declare(strict_types=1);

namespace App\Data\Auth;

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

    public static function fromArray(array $userData, array $profileData = [], array $roles = []): self
    {
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

    public function resolvedUsername(): string
    {
        return $this->username ?? str($this->email)->before('@')->slug()->toString();
    }

    public function requiresPasswordGeneration(): bool
    {
        return $this->password === null;
    }
}
