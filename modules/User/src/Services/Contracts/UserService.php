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
     * Find a user by their username.
     */
    public function findByUsername(string $username): ?User;

    /**
     * Toggle the status of a user.
     */
    public function toggleStatus(mixed $id): User;

    /**
     * Check if a user has a specific role.
     *
     * @param string $userId
     * @param string $role
     * @return bool
     */
    public function hasRole(string $userId, string $role): bool;
}
