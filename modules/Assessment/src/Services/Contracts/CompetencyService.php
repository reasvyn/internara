<?php

declare(strict_types=1);

namespace Modules\Assessment\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

interface CompetencyService extends EloquentQuery
{
    /**
     * Get competencies for a specific department.
     */
    public function getForDepartment(string $departmentId);

    /**
     * Record student competency progress.
     */
    public function recordProgress(array $data): \Modules\Assessment\Models\StudentCompetencyLog;

    /**
     * Get competency progress for a registration (Radar Chart Data).
     */
    public function getProgressStats(string $registrationId): array;
}
