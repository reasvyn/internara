<?php

declare(strict_types=1);

namespace App\Domain\Shared\Policies\Concerns;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Provides common ownership-based authorization checks for domain policies.
 *
 * Standardizes the pattern of checking if a user owns a model or is related
 * to it through a specific foreign key.
 */
trait AuthorizesOwnership
{
    protected function isOwner(User $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return $model->{$foreignKey} === $user->id;
    }

    protected function isRelatedThrough(User $user, Model $model, string $relation, string $foreignKey = 'id'): bool
    {
        $related = $model->{$relation};

        return $related !== null && $related->{$foreignKey} === $user->id;
    }

    protected function isOwnerOrAdmin(User $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isAdmin($user) || $this->isOwner($user, $model, $foreignKey);
    }
}
