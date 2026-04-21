<?php

declare(strict_types=1);

namespace Modules\Teacher\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Permission\Enums\Role;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\Teacher\Services\Contracts\TeacherService as Contract;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;
use Illuminate\Support\Str;

class TeacherService extends EloquentQuery implements Contract
{
    public function __construct(
        User $model,
        protected ProfileService $profileService,
    ) {
        $this->setModel($model);
        $this->setSearchable(['name', 'email', 'username', 'profile.registration_number']);
        $this->setSortable(['name', 'email', 'created_at']);
    }

    public function query(array $filters = [], array $columns = ['*'], array $with = []): Builder
    {
        if (! $this->baseQuery) {
            $this->setBaseQuery($this->model->newQuery()->role(Role::TEACHER->value));
        }

        return parent::query($filters, $columns, $with);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $status = $data['status'] ?? User::STATUS_ACTIVE;
            $profileData = $data['profile'] ?? [];
            unset($data['profile'], $data['status']);

            if (empty($profileData['registration_number'])) {
                $profileData['registration_number'] = 'PENDING-'.(string) Str::uuid();
            }

            if (setting('app_installed', false) && ! $this->skipAuthorization) {
                Gate::authorize('create', [User::class, [Role::TEACHER->value]]);
            }

            /** @var User $user */
            $user = $this->withoutAuthorization()->parentCreate($data);
            $user->assignRole(Role::TEACHER->value);
            $user->setStatus($status);

            if ($profileData !== []) {
                $profileService = (! setting('app_installed', false) || $this->skipAuthorization || auth()->guest())
                    ? $this->profileService->withoutAuthorization()
                    : $this->profileService;
                $profileService->upsertManagedProfile($user->id, $profileData);
            }

            $this->skipAuthorization = false;
            $user->notify(new WelcomeUserNotification());

            return $user->load(['roles:id,name', 'profile.department', 'statuses']);
        });
    }

    public function update(mixed $id, array $data): User
    {
        /** @var User $teacher */
        $teacher = $this->findOrFail($id);

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $teacher);
        }

        $status = $data['status'] ?? null;
        $profileData = $data['profile'] ?? [];
        unset($data['profile'], $data['status']);

        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        /** @var User $updatedTeacher */
        $updatedTeacher = $this->withoutAuthorization()->parentUpdate($id, $data);
        $updatedTeacher->syncRoles([Role::TEACHER->value]);

        if ($status !== null) {
            $updatedTeacher->setStatus($status);
        }

        if ($profileData !== []) {
            $profileService = $this->skipAuthorization ? $this->profileService->withoutAuthorization() : $this->profileService;
            $profileService->upsertManagedProfile($updatedTeacher->id, $profileData);
        }

        $this->skipAuthorization = false;

        return $updatedTeacher->load(['roles:id,name', 'profile.department', 'statuses']);
    }

    public function delete(mixed $id, bool $force = false): bool
    {
        /** @var User $teacher */
        $teacher = $this->findOrFail($id);

        if (! $this->skipAuthorization) {
            Gate::authorize('delete', $teacher);
        }

        $this->skipAuthorization = false;

        return $force ? $teacher->forceDelete() : $teacher->delete();
    }

    public function destroy(mixed $ids, bool $force = false): int
    {
        $teachers = $this->query()->whereKey(Arr::wrap($ids))->get();

        if (! $this->skipAuthorization) {
            foreach ($teachers as $teacher) {
                Gate::authorize('delete', $teacher);
            }
        }

        $this->skipAuthorization = false;

        return $teachers->reduce(
            fn (int $count, User $teacher): int => $count + (($force ? $teacher->forceDelete() : $teacher->delete()) ? 1 : 0),
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
