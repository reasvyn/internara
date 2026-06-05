<?php

declare(strict_types=1);

namespace App\Core\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Provides common ownership-based authorization checks for module policies.
 *
 * Standardizes the pattern of checking if a user owns a model or is related
 * to it through a specific foreign key.
 */
trait AuthorizesOwnership
{
    protected function isOwner(Model $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return $model->{$foreignKey} === $user->id;
    }

    protected function isRelatedThrough(Model $user, Model $model, string $relation, string $foreignKey = 'id'): bool
    {
        $related = $model->{$relation};

        return $related !== null && $related->{$foreignKey} === $user->id;
    }

    protected function isOwnerOrAdmin(Model $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        if ($this->isOwner($user, $model, $foreignKey)) {
            return true;
        }

        if (method_exists($this, 'isAdmin')) {
            return $this->isAdmin($user);
        }

        return false;
    }
}
