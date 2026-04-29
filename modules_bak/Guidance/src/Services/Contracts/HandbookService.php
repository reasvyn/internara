<?php

declare(strict_types=1);

namespace Modules\Guidance\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Interface HandbookService
 *
 * Defines the contract for managing instructional handbooks and student acknowledgements.
 */
interface HandbookService extends EloquentQuery
{
    /**
     * Records a student's acknowledgement of a specific handbook.
     */
    public function acknowledge(string $studentId, string $handbookId): bool;

    /**
     * Checks if a student has acknowledged a specific handbook.
     */
    public function hasAcknowledged(string $studentId, string $handbookId): bool;

    /**
     * Checks if a student has acknowledged all mandatory handbooks.
     */
    public function hasCompletedMandatory(string $studentId): bool;
}
