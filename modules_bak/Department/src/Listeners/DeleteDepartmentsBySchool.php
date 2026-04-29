<?php

declare(strict_types=1);

namespace Modules\Department\Listeners;

use Modules\Department\Services\Contracts\DepartmentService;
use Modules\School\Events\SchoolDeleted;

/**
 * Handles the autonomous cleanup of departments when a school is deleted.
 */
class DeleteDepartmentsBySchool
{
    /**
     * Create the event listener.
     */
    public function __construct(protected DepartmentService $departmentService) {}

    /**
     * Handle the event.
     */
    public function handle(SchoolDeleted $event): void
    {
        // Direct deletion for high efficiency in cleanup operations
        $this->departmentService
            ->withoutAuthorization()
            ->query()
            ->where('school_id', $event->schoolId)
            ->delete();
    }
}
