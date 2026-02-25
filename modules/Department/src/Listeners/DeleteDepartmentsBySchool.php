<?php

declare(strict_types=1);

namespace Modules\Department\Listeners;

use Modules\School\Events\SchoolDeleted;
use Modules\Department\Services\Contracts\DepartmentService;

/**
 * Handles the autonomous cleanup of departments when a school is deleted.
 */
class DeleteDepartmentsBySchool
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SchoolDeleted $event): void
    {
        // Fetch all departments belonging to the deleted school
        $departments = $this->departmentService->get(['school_id' => $event->schoolId]);

        foreach ($departments as $department) {
            $this->departmentService->delete($department->id);
        }
    }
}
