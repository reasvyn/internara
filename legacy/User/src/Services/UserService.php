<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Exception\AppException;
use Modules\Exception\RecordNotFoundException;
use Modules\Permission\Enums\Role;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;
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
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        return Cache::remember('user.stats', now()->addMinutes(10), function () {
            $roleCounts = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereIn('roles.name', [
                    Role::STUDENT->value,
                    Role::TEACHER->value,
                    Role::MENTOR->value,
                ])
                ->select('roles.name', DB::raw('count(*) as total'))
                ->groupBy('roles.name')
                ->pluck('total', 'name');

            $activeCount = $this->model
                ->newQuery()
                ->whereHas('statuses', function ($q) {
                    $q->where('name', Status::VERIFIED->value)->whereRaw(
                        'created_at = (select max(s2.created_at) from statuses as s2 where s2.model_id = users.id)',
                    );
                })
                ->count();

            return [
                'total' => $this->count(),
                'students' => $roleCounts[Role::STUDENT->value] ?? 0,
                'staff' => ($roleCounts[Role::TEACHER->value] ?? 0) +
                    ($roleCounts[Role::MENTOR->value] ?? 0),
                'active' => $activeCount,
            ];
        });
    }

    /**
     * Create a new user with specific business rules (Backward compatibility).
     */
    public function create(array $data): User
    {
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        $user = $this->createWithProfile($data, $profileData);
        Cache::forget('user.stats');

        return $user;
    }

    /**
     * Internal proxy to call parent's create without triggering local overrides.
     */
    protected function parentCreate(array $data): User
    {
        return parent::create($data);
    }

    /**
     * Create a new user and their profile atomically.
     */
    public function createWithProfile(array $userData, array $profileData = []): User
    {
        return DB::transaction(function () use ($userData, $profileData) {
            $roles = Arr::wrap($userData['roles'] ?? [Role::STUDENT->value]);
            $isPrivilegedRole =
                count(array_intersect($roles, [Role::SUPER_ADMIN->value, Role::ADMIN->value])) > 0;
            $status = $isPrivilegedRole
                ? Status::VERIFIED->value
                : $userData['status'] ?? Status::VERIFIED->value;

            if (
                in_array(Role::SUPER_ADMIN->value, $roles, true) &&
                setting('app_installed', false)
            ) {
                throw new AppException(
                    userMessage: 'user::exceptions.super_admin_readonly',
                    code: Response::HTTP_FORBIDDEN,
                );
            }

            $userData['password'] = $userData['password'] ?? Str::password(32);

            // Enforce hierarchical authority for user creation, except during initial setup
            if (setting('app_installed', false) && ! $this->skipAuthorization) {
                Gate::authorize('create', [User::class, $roles]);
            }

            // Specialized Delegation
            if (in_array(Role::SUPER_ADMIN->value, $roles)) {
                $user = $this->superAdminService->create(
                    array_merge($userData, [
                        'status' => $status,
                        'roles' => $roles,
                    ]),
                );
            } else {
                // Standard User Creation
                // [S1 - Secure] Sanitize transient fields that are not part of the database schema
                $filteredData = Arr::except($userData, [
                    'roles',
                    'status',
                    'password_confirmation',
                    'captcha_token',
                ]);

                $user = $this->withoutAuthorization()->parentCreate($filteredData);

                $this->handleUserAvatar($user, $userData['avatar_file'] ?? null);
                $user->assignRole($roles);
                $user->setStatus($status);

                // Automatically verify Admin accounts created by other administrators
                if ($isPrivilegedRole) {
                    $user->markEmailAsVerified();
                }
            }

            // UNIFIED: Initialize & Update Profile for ALL user types
            if (! empty($profileData)) {
                $profileService =
                    ! setting('app_installed', false) || $this->skipAuthorization || auth()->guest()
                        ? $this->profileService->withoutAuthorization()
                        : $this->profileService;

                $profileService->upsertManagedProfile($user->id, $profileData);
            }

            $this->skipAuthorization = false;

            $user->notify(new WelcomeUserNotification);

            Cache::forget('user.stats');

            return $user;
        });
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
            throw new RecordNotFoundException(
                message: 'exception::messages.record_not_found',
                replace: ['record' => __('user::ui.manager.table.user'), 'id' => $id],
                module: 'User',
            );
        }

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $user);
        }

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            throw new AppException(
                userMessage: 'user::exceptions.super_admin_status_cannot_be_changed',
                code: Response::HTTP_FORBIDDEN,
            );
        }

        $currentStatus = $user->latestStatus()?->name;
        $newStatus =
            $currentStatus === Status::VERIFIED->value
                ? Status::INACTIVE->value
                : Status::VERIFIED->value;

        $user->setStatus($newStatus);
        $this->skipAuthorization = false;

        Cache::forget('user.stats');

        return $user;
    }

    /**
     * Update a user's details with specific business rules.
     */
    public function update(mixed $id, array $data): User
    {
        $user = $this->find($id);

        if (! $user) {
            throw new RecordNotFoundException(
                message: 'exception::messages.record_not_found',
                replace: ['record' => __('user::ui.manager.table.user'), 'id' => $id],
                module: 'User',
            );
        }

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $user);
        }

        $roles = $data['roles'] ?? null;
        $status = $data['status'] ?? null;
        $profileData = $data['profile'] ?? [];
        unset($data['roles'], $data['status'], $data['profile']);

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            $this->skipAuthorization = false;

            return $this->superAdminService->update($id, $data);
        }

        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        $updatedUser = $this->withoutAuthorization()->parentUpdate($id, $data);
        $this->handleUserAvatar($updatedUser, $data['avatar_file'] ?? null);

        // Update basic User details
        if ($roles !== null) {
            if (
                in_array(Role::SUPER_ADMIN->value, Arr::wrap($roles), true) &&
                ! $user->hasRole(Role::SUPER_ADMIN->value)
            ) {
                throw new AppException(
                    userMessage: 'user::exceptions.super_admin_readonly',
                    code: Response::HTTP_FORBIDDEN,
                );
            }

            $updatedUser->syncRoles($roles);
            Cache::forget('user.stats');
        }

        $effectiveRoles = Arr::wrap($roles ?? $updatedUser->roles()->pluck('name')->all());
        $isPrivilegedRole =
            count(
                array_intersect($effectiveRoles, [Role::SUPER_ADMIN->value, Role::ADMIN->value]),
            ) > 0;

        if ($status !== null) {
            $updatedUser->setStatus($isPrivilegedRole ? Status::VERIFIED->value : $status);
            Cache::forget('user.stats');
        } elseif ($isPrivilegedRole) {
            $updatedUser->setStatus(Status::VERIFIED->value);
            Cache::forget('user.stats');
        }

        if ($isPrivilegedRole) {
            $updatedUser->markEmailAsVerified();
        }

        if ($profileData !== []) {
            $profileService = $this->skipAuthorization
                ? $this->profileService->withoutAuthorization()
                : $this->profileService;
            $profileService->upsertManagedProfile($updatedUser->id, $profileData);
        }

        $this->skipAuthorization = false;

        return $updatedUser;
    }

    public function sendPasswordResetLink(mixed $id): void
    {
        $user = $this->find($id);

        if (! $user) {
            throw new RecordNotFoundException(
                message: 'exception::messages.record_not_found',
                replace: ['record' => __('user::ui.manager.table.user'), 'id' => $id],
                module: 'User',
            );
        }

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $user);
        }

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            $this->skipAuthorization = false;

            throw new AppException(
                userMessage: 'user::exceptions.super_admin_readonly',
                code: Response::HTTP_FORBIDDEN,
            );
        }

        Password::sendResetLink(['email' => $user->email]);
        $this->skipAuthorization = false;
    }

    /**
     * Internal proxy to call parent's update without triggering local overrides.
     */
    protected function parentUpdate(mixed $id, array $data): User
    {
        return parent::update($id, $data);
    }

    /**
     * Internal proxy to call parent's delete without triggering local overrides.
     */
    protected function parentDelete(mixed $id, bool $force = false): bool
    {
        return parent::delete($id, $force);
    }

    /**
     * Delete a user with specific business rules.
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $user = $this->find($id);

        if (! $user) {
            throw new RecordNotFoundException(
                message: 'exception::messages.record_not_found',
                replace: ['record' => __('user::ui.manager.table.user'), 'id' => $id],
                module: 'User',
            );
        }

        if (! $this->skipAuthorization) {
            Gate::authorize('delete', $user);
        }

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            $this->skipAuthorization = false;

            return $this->superAdminService->delete($id, $force);
        }

        $result = $this->withoutAuthorization()->parentDelete($id, $force);
        $this->skipAuthorization = false;

        Cache::forget('user.stats');

        return $result;
    }

    /**
     * Handle the user's avatar update.
     */
    protected function handleUserAvatar(User &$user, UploadedFile|string|null $avatar = null): bool
    {
        return isset($avatar) ? $user->changeAvatar($avatar) : false;
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
