<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Modules\Permission\Models\Role;
use Modules\Permission\Services\Contracts\RoleService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class RoleService
 *
 * Orchestrates the management of system roles and their permissions.
 */
class RoleService extends EloquentQuery implements Contract
{
    /**
     * Create a new role service instance.
     */
    public function __construct(Role $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'module', 'description']);
        $this->setSortable(['name', 'module', 'created_at']);
    }

    /**
     * Synchronize permissions for a role.
     */
    public function syncPermissions(string $roleId, array $permissions): void
    {
        /** @var Role $role */
        $role = $this->find($roleId);
        $role->syncPermissions($permissions);
    }
}
