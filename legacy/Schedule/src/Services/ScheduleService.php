<?php

declare(strict_types=1);

namespace Modules\Schedule\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\Contracts\ScheduleService as ScheduleServiceContract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Implements the business logic for managing internship schedules and timelines.
 */
class ScheduleService extends EloquentQuery implements ScheduleServiceContract
{
    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?Schedule
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Schedule
    {
        /** @var Schedule */
        return parent::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Schedule $schedule, array $data): void
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('update', $schedule);
        }

        $this->skipAuthorization = false;

        $filteredData = $this->filterFillable($data);

        try {
            $schedule->update($filteredData);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'update_failed');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Schedule $schedule): void
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('delete', $schedule);
        }

        $this->skipAuthorization = false;

        $schedule->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarEvents(string $startDate, string $endDate): array
    {
        return $this->query()
            ->whereBetween('start_at', [$startDate, $endDate])
            ->get()
            ->toArray();
    }

    /**
     * Create a new service instance.
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
    public function getStudentTimeline(string $studentId, int $perPage = 15): LengthAwarePaginator
    {
        $registrationService = app(RegistrationService::class);
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
