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
use Modules\Log\Services\Contracts\AuditServiceInterface;
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
     * {@inheritdoc}
     */
    public function getSystemStats(): array
    {
        return [
            'total_admins' => $this->model
                ->newQuery()
                ->role([Role::ADMIN->value, Role::SUPER_ADMIN->value])
                ->count(),
            'active_admins' => $this->model
                ->newQuery()
                ->whereNotNull('email_verified_at')
                ->role([Role::ADMIN->value, Role::SUPER_ADMIN->value])
                ->count(),
            'provisioned' => $this->model->newQuery()->whereNotNull('email_verified_at')->count(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function manageUsers(array $filters): array
    {
        $query = $this->model->newQuery()->role([Role::ADMIN->value, Role::SUPER_ADMIN->value]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15)->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function auditLogs(array $filters): array
    {
        return app(AuditServiceInterface::class)
            ->query(array_merge($filters, ['subject_type' => User::class]))
            ->toArray();
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
                $profileService =
                    ! setting('app_installed', false) || $this->skipAuthorization || auth()->guest()
                        ? $this->profileService->withoutAuthorization()
                        : $this->profileService;
                $profileService->upsertManagedProfile($user->id, $profileData);
            }

            $this->skipAuthorization = false;

            return $user->load(['roles:id,name', 'profile', 'statuses']);
        });
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

        $deleted = 0;
        foreach ($admins as $admin) {
            if ($force ? $admin->forceDelete() : $admin->delete()) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
