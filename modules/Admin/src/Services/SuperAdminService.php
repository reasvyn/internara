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
        // STRICT: SuperAdmin registration is ONLY allowed during initial setup.
        if (setting('app_installed', false)) {
            throw new AppException(
                userMessage: 'auth::exceptions.registration_failed',
                logMessage: 'Attempted to register SuperAdmin after application installation.',
                code: Response::HTTP_FORBIDDEN,
            );
        }

        // Enforce setup idempotency: update existing if found by email
        return $this->save(['email' => $data['email'] ?? null], $data);
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

        // Security: Enforce standard policy if the app is already installed
        if (setting('app_installed', false) && ! $this->skipAuthorization) {
            \Illuminate\Support\Facades\Gate::authorize('update', $superAdmin);
        }

        $this->skipAuthorization = false;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($superAdmin, $data) {
            // Protect role and status from unauthorized changes
            unset($data['roles'], $data['status']);

            if (array_key_exists('password', $data) && empty($data['password'])) {
                unset($data['password']);
            }

            // Perform update using the underlying model query to bypass standard policies when needed
            $this->model->newQuery()
                ->where($this->model->getKeyName(), $superAdmin->id)
                ->update(Arr::only($data, $this->model->getFillable()));

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
        // STRICT: Save (updateOrCreate) is ONLY allowed during initial setup phase.
        if (setting('app_installed', false)) {
            throw new AppException(
                userMessage: 'auth::exceptions.registration_failed',
                code: Response::HTTP_FORBIDDEN,
            );
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($attributes, $values) {
            // Filter only fillable attributes
            $data = array_merge($attributes, $values);
            $fillableData = Arr::only($data, $this->model->getFillable());

            /** @var User $user */
            $user = $this->model->newQuery()->updateOrCreate($attributes, $fillableData);

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
