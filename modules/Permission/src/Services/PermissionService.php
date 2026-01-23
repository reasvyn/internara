<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Modules\Exception\AppException;
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
     *
     * @throws AppException If creation fails due to a database error.
     */
    public function create(array $data): Permission
    {
        try {
            /** @var Permission */
            return parent::create($data);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'creation_failed');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws AppException If the update fails due to a database error.
     */
    public function update(mixed $id, array $data, array $columns = ['*']): Permission
    {
        try {
            /** @var Permission */
            return parent::update($id, $data);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'update_failed');
        }
    }

    /**
     * Handle QueryException and wrap it in AppException.
     *
     * @throws AppException
     */
    protected function handleQueryException(QueryException $e, string $defaultKey): never
    {
        $userMessage = 'shared::exceptions.'.($e->getCode() === '23000' ? 'unique_violation' : $defaultKey);

        throw new AppException(
            userMessage: $userMessage,
            replace: ['record' => $this->recordName, 'column' => 'data'],
            logMessage: $e->getMessage(),
            code: $e->getCode() === '23000' ? 409 : 500,
            previous: $e,
        );
    }
}
