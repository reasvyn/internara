<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Internship\Models\InternshipRequirement;
use Modules\Internship\Services\Contracts\InternshipRequirementService as Contract;
use Modules\Shared\Services\EloquentQuery;

class InternshipRequirementService extends EloquentQuery implements Contract
{
    /**
     * InternshipRequirementService constructor.
     */
    public function __construct(InternshipRequirement $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'type', 'academic_year']);
        $this->setSortable(['name', 'type', 'is_mandatory', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveForYear(string $academicYear)
    {
        return $this->model
            ->newQuery()
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function submit(
        string $registrationId,
        string $requirementId,
        mixed $value = null,
        mixed $file = null,
    ): \Modules\Internship\Models\RequirementSubmission {
        $requirement = $this->find($requirementId);

        if (! $requirement) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(
                InternshipRequirement::class,
                [$requirementId],
            );
        }

        $submission = \Modules\Internship\Models\RequirementSubmission::updateOrCreate(
            ['registration_id' => $registrationId, 'requirement_id' => $requirementId],
            [
                'value' => $value,
                'status' => \Modules\Internship\Enums\SubmissionStatus::PENDING,
                'verified_at' => null,
                'verified_by' => null,
            ],
        );

        if ($file && $requirement->type === \Modules\Internship\Enums\RequirementType::DOCUMENT) {
            $submission->addMedia($file)->toMediaCollection('document');
        }

        return $submission;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(
        string $submissionId,
        string $adminId,
    ): \Modules\Internship\Models\RequirementSubmission {
        $submission = \Modules\Internship\Models\RequirementSubmission::findOrFail($submissionId);

        $submission->update([
            'status' => \Modules\Internship\Enums\SubmissionStatus::VERIFIED,
            'verified_at' => now(),
            'verified_by' => $adminId,
        ]);

        return $submission;
    }

    /**
     * {@inheritdoc}
     */
    public function reject(
        string $submissionId,
        string $adminId,
        string $notes,
    ): \Modules\Internship\Models\RequirementSubmission {
        $submission = \Modules\Internship\Models\RequirementSubmission::findOrFail($submissionId);

        $submission->update([
            'status' => \Modules\Internship\Enums\SubmissionStatus::REJECTED,
            'notes' => $notes,
            'verified_at' => null,
            'verified_by' => $adminId,
        ]);

        return $submission;
    }
}
