<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Permission\Models\Role;
use Modules\Permission\Services\Contracts\RoleService as RoleServiceContract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Service class for managing Role operations.
 *
 * @extends EloquentQuery<Role>
 */
class RoleService extends EloquentQuery implements RoleServiceContract
{
    /**
     * The name of the record for exception messages.
     */
    protected string $recordName = 'role';

    /**
     * RoleService constructor.
     */
    public function __construct(Role $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'module', 'description']);
        $this->setSortable(['name', 'module', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function list(
        array $filters = [],
        int $perPage = 10,
        array $columns = ['*'],
    ): LengthAwarePaginator {
        return $this->query($filters, $columns)
            ->when($filters['module'] ?? null, function (Builder $query, string $module) {
                $query->where('module', $module);
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function syncPermissions(string $id, array $permissions): Role
    {
        /** @var Role $role */
        $role = $this->find($id);

        if (! $role) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Role::class, [$id]);
        }

        $role->syncPermissions($permissions);

        return $role;
    }
}
