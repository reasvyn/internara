<?php

declare(strict_types=1);

namespace Modules\School\Services\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\School\Models\School;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\School\Models\School
 *
 * @extends EloquentQuery<TModel>
 */
interface SchoolService extends EloquentQuery
{
    /**
     * Retrieve schools based on conditions.
     * Note: This `get` method overrides the base `EloquentQuery::get` to ensure it works with the specific logic in SchoolService.
     * It must remain compatible with the base signature.
     *
     * @param array<string, mixed> $filters Conditions to filter the query.
     * @param array<int, string> $columns Columns to retrieve.
     * @param list<string> $with Relationships to eager load.
     *
     * @return \Illuminate\Support\Collection The found school or a collection of schools.
     */
    public function get(array $filters = [], array $columns = ['*'], array $with = []): Collection;

    public function getSchool(array $columns = ['*']): ?School;

    /**
     * List schools with optional filtering and pagination.
     *
     * @param array<string, mixed> $filters Filter criteria (e.g., 'search', 'sort').
     * @param int $perPage Number of records per page.
     * @param array<int, string> $columns Columns to retrieve.
     *
     * @return LengthAwarePaginator Paginated list of schools.
     */
}
