<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Internship\Models\Internship
 *
 * @extends EloquentQuery<TModel>
 */
interface InternshipService extends EloquentQuery
{
    /**
     * Update the program status.
     */
    public function updateStatus(string $id, string $status, ?string $reason = null): void;

    /**
     * Update the status for multiple programs in bulk.
     *
     * @param array<string> $ids
     */
    public function bulkUpdateStatus(array $ids, string $status, ?string $reason = null): void;

    /**
     * Get institutional summary metrics for internship programs.
     *
     * @return array{total: int, active: int, ongoing: int, upcoming: int}
     */
    public function getStats(): array;

    /**
     * Bulk import internship programs.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return int Number of successfully imported records.
     */
    public function import(array $rows): int;
}
