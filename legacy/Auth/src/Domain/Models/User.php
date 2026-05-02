<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Models;

use Modules\Status\Enums\Status;

/**
 * Domain Model for User Authentication.
 *
 * [S1 - Secure] Handles authentication logic and credential validation.
 * [S2 - Sustain] Encapsulates user state and business rules.
 */
class User
{
    public function __construct(
        private readonly string $id,
        private string $name,
        private string $email,
        private ?string $username,
        private array $roles = [],
        private array $statuses = [],
    ) {}

    /**
     * Check if user can authenticate.
     */
    public function canAuthenticate(): bool
    {
        return $this->isActive() && $this->hasValidCredentials();
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->getLatestStatus() === Status::ACTIVE->value;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * Check if user is verified.
     */
    public function isVerified(): bool
    {
        return $this->getLatestStatus() === Status::VERIFIED->value;
    }

    /**
     * Get the latest status name.
     */
    private function getLatestStatus(): ?string
    {
        if (empty($this->statuses)) {
            return null;
        }

        $latest = end($this->statuses);

        return $latest->name ?? null;
    }

    /**
     * Validate user has valid credentials.
     */
    private function hasValidCredentials(): bool
    {
        return ! empty($this->email) && ($this->hasRole('admin') || ! empty($this->username));
    }

    // Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
