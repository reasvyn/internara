<?php

declare(strict_types=1);

namespace Modules\Student\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Exception\RecordNotFoundException;
use Modules\Permission\Enums\Role;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Shared\Services\EloquentQuery;
use Modules\Student\Services\Contracts\StudentService as Contract;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;

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
        protected ProfileService $profileService,
    ) {
        $this->setModel($model);
        $this->setSearchable([
            'name',
            'email',
            'username',
            'profile.national_identifier',
            'profile.registration_number',
            'profile.department.name',
        ]);
        $this->setSortable(['name', 'email', 'username', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters = [], array $columns = ['*'], array $with = []): Builder
    {
        if (! $this->baseQuery) {
            $this->setBaseQuery($this->model->newQuery()->role(Role::STUDENT->value));
        }

        return parent::query($filters, $columns, $with);
    }

    /**
     * Create a new student account and profile.
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $status = $data['status'] ?? User::STATUS_PENDING;
            $profileData = $data['profile'] ?? [];
            unset($data['profile'], $data['status']);

            $data['password'] = filled($data['password'] ?? null)
                ? $data['password']
                : Str::password(32);

            if (setting('app_installed', false) && ! $this->skipAuthorization) {
                Gate::authorize('create', [User::class, [Role::STUDENT->value]]);
            }

            /** @var User $user */
            $user = $this->withoutAuthorization()->parentCreate($data);
            $user->assignRole(Role::STUDENT->value);
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

    /**
     * Update a student account and profile.
     */
    public function update(mixed $id, array $data): User
    {
        /** @var User $student */
        $student = $this->findOrFail($id);

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $student);
        }

        $status = $data['status'] ?? null;
        $profileData = $data['profile'] ?? [];
        unset($data['profile'], $data['status']);

        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        /** @var User $updatedStudent */
        $updatedStudent = $this->withoutAuthorization()->parentUpdate($id, $data);
        $updatedStudent->syncRoles([Role::STUDENT->value]);

        if ($status !== null) {
            $updatedStudent->setStatus($status);
        }

        if ($profileData !== []) {
            $profileService = $this->skipAuthorization ? $this->profileService->withoutAuthorization() : $this->profileService;
            $profileService->upsertManagedProfile($updatedStudent->id, $profileData);
        }

        $this->skipAuthorization = false;

        return $updatedStudent->load(['roles:id,name', 'profile.department', 'statuses']);
    }

    /**
     * Delete a student account.
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        /** @var User $student */
        $student = $this->findOrFail($id);

        if (! $this->skipAuthorization) {
            Gate::authorize('delete', $student);
        }

        $this->skipAuthorization = false;

        return $force ? $student->forceDelete() : $student->delete();
    }

    public function destroy(mixed $ids, bool $force = false): int
    {
        $students = $this->query()->whereKey(Arr::wrap($ids))->get();

        if (! $this->skipAuthorization) {
            foreach ($students as $student) {
                Gate::authorize('delete', $student);
            }
        }

        $this->skipAuthorization = false;

        return $students->reduce(
            fn (int $count, User $student): int => $count + (($force ? $student->forceDelete() : $student->delete()) ? 1 : 0),
            0,
        );
    }

    public function sendPasswordResetLink(mixed $id): void
    {
        /** @var User|null $student */
        $student = $this->find($id);

        if (! $student) {
            throw new RecordNotFoundException(replace: ['record' => 'Student', 'id' => $id]);
        }

        if (! $this->skipAuthorization) {
            Gate::authorize('update', $student);
        }

        Password::sendResetLink(['email' => $student->email]);
        $this->skipAuthorization = false;
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
