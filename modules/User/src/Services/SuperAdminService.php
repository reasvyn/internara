<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Http\UploadedFile;
use Modules\Exception\AppException;
use Modules\Exception\RecordNotFoundException;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;

/**
 * Service to manage super admin-related operations.
 *
 * @property User $model
 *
 * @method \Illuminate\Database\Eloquent\Builder<User> query(array $columns = ['*'])
 * @method \Illuminate\Contracts\Pagination\LengthAwarePaginator list(array $filters = [], int $perPage = 10, array $columns = ['*'])
 * @method \Modules\User\Models\User create(array $data)
 * @method \Modules\User\Models\User|null find(mixed $id, array $columns = ['*'])
 * @method bool exists(array|callable $where = [])
 * @method \Modules\User\Models\User update(mixed $id, array $data, array $columns = ['*'])
 * @method bool delete(mixed $id, bool $force = false)
 */
class SuperAdminService extends EloquentQuery implements Contracts\SuperAdminService
{
    public function __construct(User $user)
    {
        $this->setModel($user);
        $this->setSearchable(['name', 'email', 'username']);
    }

    /**
     * {@inheritdoc}
     */
    public function query(
        array $filters = [],
        array $columns = ['*'],
    ): \Illuminate\Database\Eloquent\Builder {
        if (!$this->baseQuery) {
            $this->setBaseQuery($this->model->superAdmin());
        }

        return parent::query($filters, $columns);
    }

    /**
     * Create a new SuperAdmin user.
     *
     * @param array<string, mixed> $data The data for creating the SuperAdmin user.
     *
     * @throws \Modules\Exception\AppException If a SuperAdmin already exists or creation fails.
     *
     * @return \Modules\User\Models\User The newly created SuperAdmin user.
     */
    public function create(array $data): User
    {
        if ($this->exists()) {
            throw new AppException(
                userMessage: 'user::exceptions.super_admin_exists',
                logMessage: 'Attempted to create a second SuperAdmin account.',
                code: 409,
            );
        }

        $superAdmin = parent::create($data);

        $this->handleSuperAdminAvatar($superAdmin, $data['avatar_file'] ?? null);

        $superAdmin->assignRole('super-admin');
        $superAdmin->markEmailAsVerified();

        return $superAdmin;
    }

    /**
     * Update the existing SuperAdmin user.
     *
     * @param mixed $id The primary key of the SuperAdmin user.
     * @param array<string, mixed> $data The data for updating the SuperAdmin user.
     * @param array<int, string> $columns Columns to retrieve after update.
     *
     * @throws \Modules\Exception\RecordNotFoundException If no SuperAdmin exists or the provided ID does not match the SuperAdmin.
     * @throws \Modules\Exception\AppException If the update fails.
     *
     * @return \Modules\User\Models\User The updated SuperAdmin user.
     */
    public function update(mixed $id, array $data): User
    {
        /** @var User|null $existingOwner */
        $existingOwner = $this->query(columns: ['id'])->first(); // Use the superAdmin scope to get the single SuperAdmin

        if (!$existingOwner) {
            // No SuperAdmin exists at all
            throw new RecordNotFoundException(
                userMessage: 'user::exceptions.super_admin_not_found',
                code: 404,
            );
        }

        if ($existingOwner->id !== $id) {
            throw new RecordNotFoundException(
                userMessage: 'user::exceptions.super_admin_not_found',
                replace: ['id' => $id],
                logMessage: 'Attempted to update SuperAdmin, but no SuperAdmin account exists.',
                code: 404,
            );
        }

        // Remove any role assignment from data to prevent changing SuperAdmin status
        unset($data['roles']);
        unset($data['role']);

        $updatedOwner = parent::update($existingOwner->id, $data);

        $this->handleSuperAdminAvatar($updatedOwner, $data['avatar_file'] ?? null);

        $updatedOwner->loadMissing(['roles', 'permissions']);

        return $updatedOwner;
    }

    /**
     * Update an existing SuperAdmin or create a new one if not found.
     *
     * @param array<string, mixed> $data The data for creating or updating the SuperAdmin.
     *
     * @return \Modules\User\Models\User The created or updated SuperAdmin user.
     */
    public function save(array $attributes, array $values = []): User
    {
        $superAdmin = parent::save($attributes, $values);

        $allData = array_merge($attributes, $values);
        $this->handleSuperAdminAvatar($superAdmin, $allData['avatar_file'] ?? null);

        $superAdmin->assignRole('super-admin');
        $superAdmin->markEmailAsVerified();
        $superAdmin->loadMissing(['roles', 'permissions']);

        return $superAdmin;
    }

    public function delete(mixed $id, bool $force = false): bool
    {
        /** @var User|null $superAdmin */
        $superAdmin = $this->find($id);

        if (!$superAdmin) {
            throw new RecordNotFoundException(replace: ['record' => 'SuperAdmin', 'id' => $id]);
        }

        throw new AppException(
            userMessage: 'user::exceptions.super_admin_cannot_be_deleted',
            code: 403,
        );
    }

    /**
     * Get the single SuperAdmin user.
     *
     * @param array<int, string> $columns Columns to retrieve.
     *
     * @return \Modules\User\Models\User|null The SuperAdmin user or null if not found.
     */
    public function getSuperAdmin(array $columns = ['*']): ?User
    {
        /** @var User|null $superAdmin */
        $superAdmin = $this->query($columns)->first();
        if ($superAdmin) {
            $superAdmin->loadMissing(['roles', 'permissions']);
        }

        return $superAdmin;
    }

    /**
     * Getting a collection of SuperAdmins is not allowed.
     *
     * @throws \Modules\Exception\AppException
     */
    public function get(array $filters = [], array $columns = ['*']): \Illuminate\Support\Collection
    {
        throw new AppException(
            userMessage: 'user::exceptions.cannot_get_multiple_super_admins',
            logMessage: 'Attempted to get a collection of SuperAdmins, which is not allowed.',
            code: 405,
        );
    }

    protected function handleSuperAdminAvatar(
        User &$superAdmin,
        UploadedFile|string|null $avatar = null,
    ): bool {
        return isset($avatar) ? $superAdmin->setAvatar($avatar) : false;
    }
}
