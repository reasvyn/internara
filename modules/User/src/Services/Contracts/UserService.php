<?php

declare(strict_types=1);

namespace Modules\User\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;
use Modules\User\Models\User;

/**
 * @extends EloquentQuery<User>
 */
interface UserService extends EloquentQuery
{
    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user and their profile atomically.
     */
    public function createWithProfile(array $userData, array $profileData = []): User;

    /**
     * Find a user by their username.
     */
    public function findByUsername(string $username): ?User;

    /**
     * Toggle the status of a user.
     */
    public function toggleStatus(mixed $id): User;

    /**
     * Check if a user has a specific role.
     */
    public function hasRole(string $userId, string $role): bool;
}
