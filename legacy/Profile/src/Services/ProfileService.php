<?php

declare(strict_types=1);

namespace Modules\Profile\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Gate;
use Modules\Exception\RecordNotFoundException;
use Modules\Profile\Models\Profile;
use Modules\Profile\Services\Contracts\ProfileService as Contract;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;

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
     * Find a profile by ID.
     */
    public function findById(string $id): ?Profile
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * Create a new profile.
     */
    public function create(array $data): Profile
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('create', $this->model);
        }

        /** @var Profile $profile */
        $profile = $this->model->newQuery()->create($data);
        $this->skipAuthorization = false;

        return $profile;
    }

    /**
     * Update an existing profile.
     */
    public function update(Profile $profile, array $data): void
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('update', $profile);
        }

        $profile->fill($data);
        $profile->save();
        $this->skipAuthorization = false;
    }

    /**
     * Delete a profile.
     */
    public function delete(Profile $profile): void
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('delete', $profile);
        }

        $profile->delete();
        $this->skipAuthorization = false;
    }

    /**
     * Get or create a profile for a specific user.
     */
    public function getByUserId(string $userId): ?Profile
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('view', [$this->model, $userId]);
        }

        /** @var Profile */
        return $this->model->newQuery()->firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Define the HasOne relationship for the User model.
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasOne {
        return $related->hasOne(Profile::class, $foreignKey ?? 'user_id', $localKey);
    }

    public function upsertManagedProfile(string $userId, array $data): Profile
    {
        $user = User::query()->find($userId);

        if (! $user) {
            throw new RecordNotFoundException(replace: ['record' => 'User', 'id' => $userId]);
        }

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $user);
        }

        /** @var Profile $profile */
        $profile = $this->model->newQuery()->firstOrCreate(['user_id' => $userId]);
        $profile->fill($data);
        $profile->save();

        $this->skipAuthorization = false;

        return $profile;
    }

    /**
     * Synchronize the profileable model for a profile.
     */
    public function syncProfileable(Profile $profile, Model $profileable): Profile
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('update', $profile);
        }

        if ($profile->profileable_id === $profileable->getKey()) {
            return $profile;
        }

        $profile->profileable()->associate($profileable);
        $profile->save();

        return $profile;
    }
}
