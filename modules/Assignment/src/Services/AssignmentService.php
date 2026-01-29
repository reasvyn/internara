<?php

declare(strict_types=1);

namespace Modules\Assignment\Services;

use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
use Modules\Assignment\Models\Submission;
use Modules\Assignment\Services\Contracts\AssignmentService as Contract;
use Modules\Shared\Services\EloquentQuery;

class AssignmentService extends EloquentQuery implements Contract
{
    public function __construct(Assignment $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaults(string $internshipId, ?string $academicYear = null): void
    {
        $types = AssignmentType::all();

        foreach ($types as $type) {
            $this->model->newQuery()->firstOrCreate(
                [
                    'assignment_type_id' => $type->id,
                    'internship_id' => $internshipId,
                ],
                [
                    'title' => $type->name,
                    'description' => $type->description,
                    'is_mandatory' => true,
                    'academic_year' => $academicYear,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFulfillmentComplete(string $registrationId): bool
    {
        // 1. Get all mandatory assignments for the program associated with this registration
        // (Assuming we can find the program from registration, but since we don't have direct access here,
        // we might need registration details passed in or fetched)
        
        $registration = app(\Modules\Internship\Services\Contracts\InternshipRegistrationService::class)
            ->find($registrationId);

        if (!$registration) {
            return false;
        }

        $mandatoryAssignments = $this->model->newQuery()
            ->where('internship_id', $registration->internship_id)
            ->where('is_mandatory', true)
            ->get();

        if ($mandatoryAssignments->isEmpty()) {
            return true;
        }

        // 2. Check if every mandatory assignment has a verified submission
        foreach ($mandatoryAssignments as $assignment) {
            $hasVerified = Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('registration_id', $registrationId)
                ->currentStatus('verified')
                ->exists();

            if (!$hasVerified) {
                return false;
            }
        }

        return true;
    }
}
