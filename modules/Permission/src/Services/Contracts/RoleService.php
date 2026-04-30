<?php

declare(strict_types=1);

namespace Modules\Permission\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Permission\Models\Role
 *
 * @extends EloquentQuery<TModel>
 */
interface RoleService extends EloquentQuery
{
    /**
     * Synchronize permissions for a role.
     *
     * @param array<string> $permissions
     */
    public function syncPermissions(string $roleId, array $permissions): void;
}
