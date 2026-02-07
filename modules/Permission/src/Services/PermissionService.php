<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Modules\Permission\Models\Permission;
use Modules\Permission\Services\Contracts\PermissionService as Contract;
use Modules\Shared\Services\EloquentQuery;

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
    public function __construct(Permission $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'module', 'description']);
        $this->setSortable(['name', 'module', 'created_at']);
    }
}
