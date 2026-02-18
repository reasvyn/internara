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
        $types = AssignmentType::select(['id', 'name', 'group', 'description'])->get();

        foreach ($types as $type) {
            $this->model->newQuery()->firstOrCreate(
                [
                    'assignment_type_id' => $type->id,
                    'internship_id' => $internshipId,
                ],
                [
                    'title' => $type->name,
                    'group' => $type->group,
                    'description' => $type->description,
                    'is_mandatory' => true,
                    'academic_year' => $academicYear,
                ],
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFulfillmentComplete(string $registrationId, ?string $group = null): bool
    {
        // 1. Get all mandatory assignments for the program associated with this registration
        // Note: Using app() resolution to avoid circular dependency with RegistrationService
        $registration = app(
            \Modules\Internship\Services\Contracts\RegistrationService::class,
        )->find($registrationId);

        if (! $registration) {
            return false;
        }

        $query = $this->model
            ->newQuery()
            ->select(['id'])
            ->where('internship_id', $registration->internship_id)
            ->where('is_mandatory', true);

        if ($group) {
            $query->where('group', $group);
        }

        $mandatoryAssignments = $query->get();

        if ($mandatoryAssignments->isEmpty()) {
            return true;
        }

        // 2. Efficiently count unique verified mandatory assignments in one query
        $verifiedCount = Submission::query()
            ->whereIn('assignment_id', $mandatoryAssignments->pluck('id'))
            ->where('registration_id', $registrationId)
            ->whereHas('statuses', function ($query) {
                $query->where('name', 'verified')->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            })
            ->distinct('assignment_id')
            ->count('assignment_id');

        return $verifiedCount === $mandatoryAssignments->count();
    }
}
