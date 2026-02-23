<?php

declare(strict_types=1);

namespace Modules\Student\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\Student\Models\Student;
use Modules\Student\Services\Contracts\StudentService as Contract;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Implements the business logic for students (Account + Profile).
 */
class StudentService extends EloquentQuery implements Contract
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        User $model,
        protected UserService $userService,
        protected ProfileService $profileService
    ) {
        $this->setModel($model);
        $this->setSearchable(['name', 'email', 'username', 'profile.national_identifier']);
        $this->setSortable(['name', 'email', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters = [], array $columns = ['*']): Builder
    {
        // Force strict role filtering for all queries
        if (! $this->baseQuery) {
            $this->setBaseQuery($this->model->role('student'));
        }

        return parent::query($filters, $columns);
    }

    /**
     * Create a new student account and profile.
     */
    public function create(array $data): User
    {
        // Force the student role
        $data['roles'] = ['student'];
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        // 1. Create User account via UserService
        $user = $this->userService->create($data);

        // 2. Link & Update Profile with international identity standards
        $profile = $this->profileService->getByUserId($user->id);
        $this->profileService->update($profile->id, $profileData);

        return $user;
    }

    /**
     * Update a student account and profile.
     */
    public function update(mixed $id, array $data): User
    {
        // Ensure we are only updating a student
        $user = $this->find($id);

        if (! $user || ! $user->hasRole('student')) {
            throw new \Modules\Exception\RecordNotFoundException(
                replace: ['record' => 'Student', 'id' => $id]
            );
        }

        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        // 1. Update User account
        $user = $this->userService->update($id, $data);

        // 2. Update Unified Profile data
        if (! empty($profileData) && $user->profile) {
            $this->profileService->update($user->profile->id, $profileData);
        }

        return $user;
    }

    /**
     * Delete a student account.
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $user = $this->find($id);

        if (! $user || ! $user->hasRole('student')) {
            return false;
        }

        // 1. Delete User account (Profile is usually handled by Cascade or UserService if needed)
        return $this->userService->delete($id, $force);
    }
}
