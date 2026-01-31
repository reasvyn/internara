<?php

declare(strict_types=1);

namespace Modules\Schedule\Services\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Interface ScheduleService
 *
 * Defines the contract for managing internship-related events and timelines.
 */
interface ScheduleService extends EloquentQuery
{
    /**
     * Get the timeline of events for a specific student's journey.
     *
     * @param string $studentId The UUID of the student.
     */
    public function getStudentTimeline(string $studentId): Collection;

    /**
     * Get events filtered by academic year.
     *
     * @param string $academicYearId The ID of the academic year.
     */
    public function getByAcademicYear(string $academicYearId): Collection;
}
