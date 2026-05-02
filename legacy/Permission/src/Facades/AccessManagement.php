<?php

declare(strict_types=1);

namespace Modules\Permission\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Permission\Services\AccessManagementService;
use Modules\Permission\Services\Contracts\AccessManagementService as AccessManagementServiceContract;

/**
 * Facade for access management operations.
 *
 * @method static \Modules\Permission\Models\Permission createPermission(string $name, string $description, string $module, string $guardName = 'web')
 * @method static \Modules\Permission\Models\Role createRole(string $name, string $description, string $module, string $guardName = 'web')
 * @method static \Modules\Permission\Models\Role assignPermissionsToRole(string $roleName, array $permissions, string $guardName = 'web')
 * @method static bool deleteRole(string $roleName, string $guardName = 'web')
 * @method static bool deletePermission(string $permissionName, string $guardName = 'web')
 * @method static void refreshCache()
 *
 * @see AccessManagementService
 */
class AccessManagement extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccessManagementServiceContract::class;
    }
}
