<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Permission\Models\Permission;
use Modules\Permission\Services\Contracts\PermissionService as PermissionServiceContract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Service class for managing Permission operations.
 *
 * @extends EloquentQuery<Permission>
 */
class PermissionService extends EloquentQuery implements PermissionServiceContract
{
    /**
     * The name of the record for exception messages.
     */
    protected string $recordName = 'permission';

    /**
     * PermissionService constructor.
     */
    public function __construct(Permission $model)
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
    public function create(array $data): Permission
    {
        /** @var Permission */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(mixed $id, array $data, array $columns = ['*']): Permission
    {
        /** @var Permission */
        return parent::update($id, $data);
    }
}
