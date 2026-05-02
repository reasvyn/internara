<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Modules\Permission\Enums\Permission;
use Modules\Permission\Models\Permission as PermissionModel;
use Modules\Permission\Services\Contracts\PermissionService as Contract;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Models\User;

/**
 * Class PermissionService
 *
 * Manages the lifecycle and technical orchestration of system permissions.
 */
class PermissionService extends EloquentQuery implements Contract
{
    /**
     * Create a new permission service instance.
     */
    public function __construct(PermissionModel $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'module', 'description']);
        $this->setSortable(['name', 'module', 'created_at']);
    }

    /**
     * Find a permission by ID.
     */
    public function findById(string $id): ?object
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * Get all available roles from Role enum.
     *
     * @return array<string>
     */
    public function getRoles(): array
    {
        return array_map(fn (Role $role) => $role->value, Role::cases());
    }

    /**
     * Assign a role to a user using Spatie's HasRoles trait.
     */
    public function assignRole(string $userId, string $role): void
    {
        $user = User::findOrFail($userId);
        $user->syncRoles([$role]);
    }

    /**
     * Check if a user has a specific permission.
     */
    public function hasPermission(string $userId, Permission $permission): bool
    {
        $user = User::findOrFail($userId);

        // Super admin has all permissions (use Role enum)
        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            return true;
        }

        // Check via Spatie Permission
        return $user->hasPermissionTo($permission->value);
    }

    /**
     * Get permission options for dropdown.
     *
     * @return array<array{value: string, label: string}>
     */
    public function getDropdownOptions(): array
    {
        return array_map(
            fn (Permission $permission) => [
                'value' => $permission->value,
                'label' => __($permission->value),
            ],
            Permission::cases()
        );
    }
}
