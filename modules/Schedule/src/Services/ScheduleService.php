<?php

declare(strict_types=1);

namespace Modules\Schedule\Services;

use Illuminate\Support\Collection;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\Contracts\ScheduleService as ScheduleServiceContract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class ScheduleService
 *
 * Implements the business logic for managing internship schedules and timelines.
 */
class ScheduleService extends EloquentQuery implements ScheduleServiceContract
{
    /**
     * Create a new ScheduleService instance.
     */
    public function __construct(Schedule $model)
    {
        $this->setModel($model);
        $this->setSearchable(['title', 'description', 'location']);
        $this->setSortable(['start_at', 'end_at', 'title']);
    }

    /**
     * {@inheritdoc}
     */
    public function getStudentTimeline(
        string $studentId,
        int $perPage = 15,
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $registrationService = app(
            \Modules\Internship\Services\Contracts\RegistrationService::class,
        );
        $registration = $registrationService->first(['student_id' => $studentId]);

        $query = $this->query()->orderBy('start_at', 'asc');

        if ($registration) {
            $query->where(function ($q) use ($registration) {
                $q->where('internship_id', $registration->internship_id)->orWhereNull(
                    'internship_id',
                );
            });
        } else {
            // If no registration, only show global events
            $query->whereNull('internship_id');
        }

        return $query->paginate([], $perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByAcademicYear(string $academicYearId): Collection
    {
        return $this->query(['academic_year' => $academicYearId])
            ->orderBy('start_at', 'asc')
            ->get();
    }
}
