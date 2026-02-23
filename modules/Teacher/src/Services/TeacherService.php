<?php

declare(strict_types=1);

namespace Modules\Teacher\Services;

use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\Teacher\Services\Contracts\TeacherService as Contract;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

class TeacherService extends EloquentQuery implements Contract
{
    public function __construct(
        User $model,
        protected UserService $userService,
        protected ProfileService $profileService
    ) {
        $this->setModel($model);
        $this->setSearchable(['profile.registration_number']);
    }

    /**
     * Create a new teacher account and profile.
     */
    public function create(array $data): User
    {
        // Force the teacher role
        $data['roles'] = ['teacher'];
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        // Set default registration number if missing
        if (empty($profileData['registration_number'])) {
            $profileData['registration_number'] = 'PENDING-'.(string) \Illuminate\Support\Str::uuid();
        }

        // 1. Create User account
        $user = $this->userService->create($data);

        // 2. Link & Update Profile
        $profile = $this->profileService->getByUserId($user->id);
        $this->profileService->update($profile->id, $profileData);

        return $user;
    }

    /**
     * Update a teacher account and profile.
     */
    public function update(mixed $id, array $data): User
    {
        $user = $this->userService->find($id);

        if (! $user || ! $user->hasRole('teacher')) {
            throw new \Modules\Exception\RecordNotFoundException(
                replace: ['record' => 'Teacher', 'id' => $id]
            );
        }

        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        // 1. Update User account
        $user = $this->userService->update($id, $data);

        // 2. Update Profile data
        if (! empty($profileData) && $user->profile) {
            $this->profileService->update($user->profile->id, $profileData);
        }

        return $user;
    }
}
