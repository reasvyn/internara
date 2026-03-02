<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Support\Arr;
use Modules\Admin\Services\Contracts\SuperAdminService as Contract;
use Modules\Exception\AppException;
use Modules\Exception\RecordNotFoundException;
use Modules\Permission\Enums\Role;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service to manage the authoritative SuperAdmin account.
 * 
 * This service operates independently of UserService to ensure that 
 * the highest level of administrative access is handled with specific 
 * business rules and tighter security constraints.
 */
class SuperAdminService extends EloquentQuery implements Contract
{
    public function __construct(User $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'email', 'username']);
    }

    /**
     * {@inheritdoc}
     */
    public function getSuperAdmin(): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()->role(Role::SUPER_ADMIN->value)->first();

        if ($user) {
            $user->loadMissing(['roles', 'permissions']);
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): User
    {
        // Enforce setup idempotency if app is not installed
        if (! setting('app_installed', false)) {
            return $this->save(['email' => $data['email'] ?? null], $data);
        }

        if ($this->getSuperAdmin()) {
            throw new AppException(
                userMessage: 'auth::exceptions.super_admin_already_exists',
                code: Response::HTTP_CONFLICT,
            );
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $plainPassword = $data['password'] ?? \Illuminate\Support\Str::password(16);
            $data['password'] = $plainPassword;
            $data['email_verified_at'] = now();

            // Create record without user-level authorization checks
            /** @var User $user */
            $user = $this->withoutAuthorization()->model->newQuery()->create(
                Arr::only($data, $this->model->getFillable())
            );

            $user->assignRole(Role::SUPER_ADMIN->value);
            $user->setStatus(User::STATUS_ACTIVE);

            // Handle Avatar if provided
            if (isset($data['avatar_file'])) {
                $user->changeAvatar($data['avatar_file']);
            }

            $user->notify(new WelcomeUserNotification($plainPassword));

            return $user;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function update(mixed $id, array $data): User
    {
        $superAdmin = $this->getSuperAdmin();

        if (! $superAdmin || $superAdmin->id !== $id) {
            throw new RecordNotFoundException(
                userMessage: 'auth::exceptions.super_admin_not_found',
                replace: ['id' => $id],
                module: 'Admin'
            );
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($superAdmin, $data) {
            // Protect role and status from unauthorized changes via this service
            unset($data['roles'], $data['status']);

            if (isset($data['password']) && empty($data['password'])) {
                unset($data['password']);
            }

            // Perform update bypassing standard UserPolicy
            $this->withoutAuthorization()->update($superAdmin->id, $data);

            if (isset($data['avatar_file'])) {
                $superAdmin->changeAvatar($data['avatar_file']);
            }

            return $superAdmin->fresh(['roles', 'permissions']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $attributes, array $values = []): User
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($attributes, $values) {
            $isSetup = ! setting('app_installed', false);
            
            // Filter only fillable attributes
            $data = array_merge($attributes, $values);
            $fillableData = Arr::only($data, $this->model->getFillable());

            // If setup, allow promoting existing user to SuperAdmin
            $query = $isSetup ? $this->model->newQuery() : $this->model->newQuery()->role(Role::SUPER_ADMIN->value);
            
            /** @var User $user */
            $user = $query->updateOrCreate($attributes, $fillableData);

            if (! $user->hasRole(Role::SUPER_ADMIN->value)) {
                $user->assignRole(Role::SUPER_ADMIN->value);
            }

            $user->setStatus(User::STATUS_ACTIVE);
            $user->markEmailAsVerified();

            if (isset($data['avatar_file'])) {
                $user->changeAvatar($data['avatar_file']);
            }

            return $user->loadMissing(['roles', 'permissions']);
        });
    }
}
