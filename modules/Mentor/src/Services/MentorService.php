<?php

declare(strict_types=1);

namespace Modules\Mentor\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Mentor\Services\Contracts\MentorService as Contract;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Orchestrator for Industry Mentor users.
 */
class MentorService extends EloquentQuery implements Contract
{
    public function __construct(
        User $model,
        protected UserService $userService,
        protected ProfileService $profileService,
    ) {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters = [], array $columns = ['*']): Builder
    {
        if (! $this->baseQuery) {
            $this->setBaseQuery($this->model->role('mentor'));
        }

        return parent::query($filters, $columns);
    }

    /**
     * Create a new Mentor account and Profile.
     */
    public function create(array $data): User
    {
        $data['roles'] = ['mentor'];
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        // 1. Create User account
        $user = $this->userService->create($data);

        // 2. Create Profile record (Mentors have Profile but no profileable model)
        if (! empty($profileData)) {
            $profile = $this->profileService->getByUserId($user->id);
            $this->profileService->update($profile->id, $profileData);
        }

        return $user;
    }

    /**
     * Update a Mentor account and Profile.
     */
    public function update(mixed $id, array $data): User
    {
        $user = $this->find($id);

        if (! $user || ! $user->hasRole('mentor')) {
            throw new \Modules\Exception\RecordNotFoundException(
                replace: ['record' => 'Mentor', 'id' => $id],
            );
        }

        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        // 1. Update User account
        $user = $this->userService->update($id, $data);

        // 2. Update Profile record if it exists
        if (! empty($profileData)) {
            $profile = $this->profileService->getByUserId($user->id);
            $this->profileService->update($profile->id, $profileData);
        }

        return $user;
    }

    /**
     * Delete a Mentor account.
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $user = $this->find($id);

        if (! $user || ! $user->hasRole('mentor')) {
            return false;
        }

        return $this->userService->delete($id, $force);
    }
}
