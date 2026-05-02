<?php

declare(strict_types=1);

namespace Modules\Assignment\Services\Contracts;

use Illuminate\Pagination\Paginator;
use Modules\Assignment\Models\Assignment;
use Modules\Shared\Services\Contracts\EloquentQuery;

interface AssignmentService extends EloquentQuery
{
    public function findById(string $id): ?Assignment;

    public function create(array $data): Assignment;

    public function update(Assignment $assignment, array $data): void;

    public function delete(Assignment $assignment): bool;

    public function paginate(int $perPage = 15): Paginator;
}
