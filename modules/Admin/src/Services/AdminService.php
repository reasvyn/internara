<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Admin\Services\Contracts\AdminService as Contract;
use Modules\Permission\Enums\Role;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;

/**
 * Orchestrator for Administrative users.
 *
 * This service manages administrators within their own bounded context.
 */
class AdminService extends EloquentQuery implements Contract
{
    public function __construct(
        User $model,
        protected ProfileService $profileService,
    ) {
        $this->setModel($model);
        $this->setSearchable(['name', 'email', 'username']);
        $this->setSortable(['name', 'email', 'created_at']);
    }

    public function query(array $filters = [], array $columns = ['*'], array $with = []): Builder
    {
        if (! $this->baseQuery) {
            $this->setBaseQuery($this->model->newQuery()->role(Role::ADMIN->value));
        }

        return parent::query($filters, $columns, $with);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $status = $data['status'] ?? User::STATUS_ACTIVE;
            $profileData = Arr::only($data['profile'] ?? [], ['phone', 'address', 'gender']);
            unset($data['profile'], $data['status'], $data['roles']);

            if (setting('app_installed', false) && ! $this->skipAuthorization) {
                Gate::authorize('create', [User::class, [Role::ADMIN->value]]);
            }

            /** @var User $user */
            $user = $this->withoutAuthorization()->parentCreate($data);
            $user->assignRole(Role::ADMIN->value);
            $user->setStatus($status);
            $user->markEmailAsVerified();

            $profile = $this->profileService->withoutAuthorization()->getByUserId($user->id);
            if ($profileData !== []) {
                $this->profileService->withoutAuthorization()->update($profile->id, $profileData);
            }

            $this->skipAuthorization = false;
            $user->notify(new WelcomeUserNotification());

            return $user->load(['roles:id,name', 'profile', 'statuses']);
        });
    }

    public function update(mixed $id, array $data): User
    {
        /** @var User $admin */
        $admin = $this->findOrFail($id);

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $admin);
        }

        $status = $data['status'] ?? null;
        $profileData = Arr::only($data['profile'] ?? [], ['phone', 'address', 'gender']);
        unset($data['profile'], $data['status'], $data['roles']);

        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        /** @var User $updatedAdmin */
        $updatedAdmin = $this->withoutAuthorization()->parentUpdate($id, $data);
        $updatedAdmin->syncRoles([Role::ADMIN->value]);

        if ($status !== null) {
            $updatedAdmin->setStatus($status);
        }

        $profile = $this->profileService->withoutAuthorization()->getByUserId($updatedAdmin->id);
        if ($profileData !== []) {
            $this->profileService->withoutAuthorization()->update($profile->id, $profileData);
        }

        $this->skipAuthorization = false;

        return $updatedAdmin->load(['roles:id,name', 'profile', 'statuses']);
    }

    public function destroy(mixed $ids, bool $force = false): int
    {
        $admins = $this->query()->whereKey(Arr::wrap($ids))->get();

        if (! $this->skipAuthorization) {
            foreach ($admins as $admin) {
                Gate::authorize('delete', $admin);
            }
        }

        $this->skipAuthorization = false;

        return $admins->reduce(
            fn (int $count, User $admin): int => $count + (($force ? $admin->forceDelete() : $admin->delete()) ? 1 : 0),
            0,
        );
    }

    protected function parentCreate(array $data): User
    {
        /** @var User */
        return parent::create($data);
    }

    protected function parentUpdate(mixed $id, array $data): User
    {
        /** @var User */
        return parent::update($id, $data);
    }
}
