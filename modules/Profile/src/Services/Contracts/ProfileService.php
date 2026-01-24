<?php

declare(strict_types=1);

namespace Modules\Profile\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Profile\Models\Profile;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<Profile>
 */
interface ProfileService extends EloquentQuery
{
    /**
     * Define the HasOne relationship for the User model.
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasOne;

    /**
     * Get or create a profile for a specific user.
     */
    public function getByUserId(string $userId): Profile;

    /**
     * Synchronize the profileable model based on user roles.
     */
    public function syncProfileable(Profile $profile, array $roles): Profile;
}
