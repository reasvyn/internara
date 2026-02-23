<?php

declare(strict_types=1);

namespace Modules\Profile\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Permission\Enums\Role;
use Modules\Profile\Models\Profile;
use Modules\Profile\Services\Contracts\ProfileService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * @property Profile $model
 */
class ProfileService extends EloquentQuery implements Contract
{
    public function __construct(Profile $model)
    {
        $this->setModel($model);
    }

    /**
     * Define the HasOne relationship for the User model.
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasOne {
        return $related->hasOne(Profile::class, $foreignKey ?: 'user_id', $localKey);
    }

    /**
     * Get or create a profile for a specific user.
     */
    public function getByUserId(string $userId): Profile
    {
        /** @var Profile */
        return $this->model->newQuery()->firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Synchronize the profileable model for a profile.
     */
    public function syncProfileable(Profile $profile, Model $profileable): Profile
    {
        if ($profile->profileable_id === $profileable->getKey()) {
            return $profile;
        }

        $profile->profileable()->associate($profileable);
        $profile->save();

        return $profile;
    }
}
