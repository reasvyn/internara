<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Admin\Services\Contracts\AdminService as Contract;
use Modules\Permission\Enums\Role;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\AccountProvisioningService;

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
        protected AccountProvisioningService $provisioning,
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

    /**
     * @return Authenticatable&Model
     */
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $status = $data['status'] ?? User::STATUS_ACTIVE;
            $profileData = Arr::only($data['profile'] ?? [], ['phone', 'address', 'gender']);
            unset($data['profile'], $data['status'], $data['roles']);

            // Admin accounts are provisioned via invitation — never let the admin set the password.
            // Generate a cryptographically strong placeholder that nobody knows.
            $data['password'] = Str::password(32);

            if (setting('app_installed', false) && ! $this->skipAuthorization) {
                Gate::authorize('create', [User::class, [Role::ADMIN->value]]);
            }

            /** @var User $user */
            $user = $this->withoutAuthorization()->parentCreate($data);
            $user->assignRole(Role::ADMIN->value);
            $user->setStatus($status);
            // Email verification happens when invitation is accepted (inbox proof)

            if ($profileData !== []) {
                $profileService = (! setting('app_installed', false) || $this->skipAuthorization || auth()->guest())
                    ? $this->profileService->withoutAuthorization()
                    : $this->profileService;
                $profileService->upsertManagedProfile($user->id, $profileData);
            }

            $this->skipAuthorization = false;

            return $user->load(['roles:id,name', 'profile', 'statuses']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function invite(
        Authenticatable&Model $admin,
        (Authenticatable&Model)|null $issuedBy = null,
        int $expiresInDays = 7
    ): void {
        /** @var User $admin */
        if (! $admin->requiresSetup()) {
            throw new \RuntimeException('Cannot reinvite an admin who has already claimed their account.');
        }

        $this->provisioning->invite($admin, $expiresInDays, $issuedBy);
    }

    /**
     * @return Authenticatable&Model
     */
    public function update(mixed $id, array $data): Model
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

        if ($profileData !== []) {
            $profileService = $this->skipAuthorization ? $this->profileService->withoutAuthorization() : $this->profileService;
            $profileService->upsertManagedProfile($updatedAdmin->id, $profileData);
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
