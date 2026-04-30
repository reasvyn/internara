<?php

declare(strict_types=1);

namespace Modules\Department\Services\Contracts;

use Modules\Department\Models\Department;
use Modules\Shared\Services\Contracts\EloquentQuery;

interface DepartmentService extends EloquentQuery
{
    public function findById(string $id): ?Department;

    public function create(array $data): Department;

    public function update(Department $department, array $data): void;

    public function delete(Department $department): bool;

    public function paginate(int $perPage = 15): \Illuminate\Pagination\Paginator;

    public function getDropdownOptions(): array;
}
