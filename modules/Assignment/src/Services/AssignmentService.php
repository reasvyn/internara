<?php

declare(strict_types=1);

namespace Modules\Assignment\Services;

use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
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
        // 1. Resolve Registration Metadata (via Contract)
        $registration = app(
            \Modules\Internship\Services\Contracts\RegistrationService::class,
        )->find($registrationId);

        if (! $registration) {
            return false;
        }

        // 2. Identify Mandatory Assignments for this program
        $query = Assignment::where('internship_id', $registration->internship_id)->where(
            'is_mandatory',
            true,
        );

        if ($group) {
            $query->where('group', $group);
        }

        $mandatoryCount = $query->count();

        if ($mandatoryCount === 0) {
            return true;
        }

        // 3. Count Verified Submissions
        $verifiedCount = Submission::where('registration_id', $registrationId)
            ->whereRelation('statuses', 'name', 'verified')
            ->whereIn('assignment_id', $query->pluck('id'))
            ->count();

        return $verifiedCount >= $mandatoryCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes(): \Illuminate\Support\Collection
    {
        return AssignmentType::all();
    }
}
