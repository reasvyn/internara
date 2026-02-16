<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Modules\Exception\AppException;
use Modules\Exception\RecordNotFoundException;
use Modules\Permission\Enums\Role;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;
use Modules\User\Services\Contracts\SuperAdminService;
use Modules\User\Services\Contracts\UserService as Contract;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property User $model
 */
class UserService extends EloquentQuery implements Contract
{
    /**
     * UserService constructor.
     */
    public function __construct(
        User $model,
        protected SuperAdminService $superAdminService,
        protected ProfileService $profileService,
    ) {
        $this->setModel($model);
        $this->setSearchable(['name', 'email', 'username']);
        $this->setSortable(['name', 'email', 'username', 'created_at']);
    }

    /**
     * Create a new user with specific business rules.
     */
    public function create(array $data): User
    {
        $roles = $data['roles'] ?? null;
        $status = $data['status'] ?? User::STATUS_ACTIVE;
        $profileData = $data['profile'] ?? [];
        unset($data['roles'], $data['status'], $data['profile']);

        // Default role for new users if not specified (e.g., from public registration)
        if ($roles === null) {
            $roles = [Role::STUDENT->value];
        }

        if ($roles !== null) {
            $roles = Arr::wrap($roles);
            if (in_array(Role::SUPER_ADMIN->value, $roles)) {
                // Specialized SuperAdmin creation logic
                return $this->superAdminService->create($data);
            }
        }

        $plainPassword = $data['password'] ?? \Illuminate\Support\Str::password(16);
        $data['password'] = $plainPassword;

        $user = parent::create($data);
        $this->handleUserAvatar($user, $data['avatar_file'] ?? null);

        // Initialize Profile
        $profile = $this->profileService->getByUserId($user->id);
        if ($roles !== null) {
            $this->profileService->syncProfileable($profile, $roles, $profileData);
        }

        if (! empty($profileData)) {
            $this->profileService->update($profile->id, $profileData);
        }

        if ($roles !== null) {
            $user->assignRole($roles);

            // Automatically verify Admin accounts created by other administrators
            if (in_array(Role::ADMIN->value, Arr::wrap($roles))) {
                $user->markEmailAsVerified();
            }
        }

        $user->setStatus($status);

        // Notify the new user
        $user->notify(new WelcomeUserNotification($plainPassword));

        return $user;
    }

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    /**
     * Find a user by their username.
     */
    public function findByUsername(string $username): ?User
    {
        return $this->model->newQuery()->where('username', $username)->first();
    }

    /**
     * Toggle the status of a user between active and inactive.
     */
    public function toggleStatus(mixed $id): User
    {
        $user = $this->find($id);

        if (! $user) {
            throw new RecordNotFoundException(replace: ['record' => 'User', 'id' => $id]);
        }

        Gate::authorize('update', $user);

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            throw new AppException(
                userMessage: 'user::exceptions.super_admin_status_cannot_be_changed',
                code: Response::HTTP_FORBIDDEN,
            );
        }

        $currentStatus = $user->latestStatus()?->name;
        $newStatus =
            $currentStatus === User::STATUS_ACTIVE ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;

        $user->setStatus($newStatus);

        return $user;
    }

    /**
     * Update a user's details with specific business rules.
     */
    public function update(mixed $id, array $data): User
    {
        $user = $this->find($id);

        if (! $user) {
            throw new RecordNotFoundException(replace: ['record' => 'User', 'id' => $id]);
        }

        Gate::authorize('update', $user);

        $roles = $data['roles'] ?? null;
        $status = $data['status'] ?? null;
        $profileData = $data['profile'] ?? [];
        unset($data['roles'], $data['status'], $data['profile']);

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            return $this->superAdminService->update($id, $data);
        }

        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        $updatedUser = parent::update($id, $data);
        $this->handleUserAvatar($updatedUser, $data['avatar_file'] ?? null);

        // Update Profile
        $profile = $this->profileService->getByUserId($updatedUser->id);
        if ($roles !== null) {
            $updatedUser->syncRoles($roles);
            $this->profileService->syncProfileable($profile, Arr::wrap($roles), $profileData);
        }

        if (! empty($profileData)) {
            $this->profileService->update($profile->id, $profileData);
        }

        if ($status !== null) {
            $updatedUser->setStatus($status);
        }

        return $updatedUser;
    }

    /**
     * Delete a user with specific business rules.
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $user = $this->find($id);

        if (! $user) {
            throw new RecordNotFoundException(replace: ['record' => 'User', 'id' => $id]);
        }

        Gate::authorize('delete', $user);

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            return $this->superAdminService->delete($id, $force);
        }

        return parent::delete($id, $force);
    }

    /**
     * Handle the user's avatar update.
     */
    protected function handleUserAvatar(User &$user, UploadedFile|string|null $avatar = null): bool
    {
        return isset($avatar) ? $user->setAvatar($avatar) : false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasRole(string $userId, string $role): bool
    {
        $user = $this->find($userId);

        if (! $user) {
            return false;
        }

        return $user->hasRole($role);
    }
}
