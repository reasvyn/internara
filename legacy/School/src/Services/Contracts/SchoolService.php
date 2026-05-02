<?php

declare(strict_types=1);

namespace Modules\School\Services\Contracts;

use Illuminate\Pagination\Paginator;
use Modules\School\Models\School;
use Modules\Shared\Services\Contracts\EloquentQuery;

interface SchoolService extends EloquentQuery
{
    public function findById(string $id): ?School;

    public function create(array $data): School;

    public function update(School $school, array $data): void;

    public function delete(School $school): bool;

    public function getDepartments(string $schoolId): array;

    public function paginate(int $perPage = 15): Paginator;
}
