<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Internship\Enums\RequirementType;
use Modules\Internship\Enums\SubmissionStatus;
use Modules\Internship\Models\InternshipRequirement;
use Modules\Internship\Models\RequirementSubmission;
use Modules\Internship\Services\Contracts\InternshipRequirementService as Contract;
use Modules\Internship\Services\Contracts\RegistrationService;
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
    public function getStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'mandatory' => $this->query(['is_mandatory' => true])->count(),
            'active' => $this->query(['is_active' => true])->count(),
            'documents' => $this->query(['type' => 'document'])->count(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function submit(
        string $registrationId,
        string $requirementId,
        mixed $value = null,
        mixed $file = null,
    ): RequirementSubmission {
        $requirement = $this->find($requirementId);

        if (!$requirement) {
            $e = new ModelNotFoundException();
            throw $e->setModel(InternshipRequirement::class, [$requirementId]);
        }

        $submission = RequirementSubmission::updateOrCreate(
            ['registration_id' => $registrationId, 'requirement_id' => $requirementId],
            [
                'value' => $value,
                'status' => SubmissionStatus::PENDING,
                'verified_at' => null,
                'verified_by' => null,
            ],
        );

        if ($file && $requirement->type === RequirementType::DOCUMENT) {
            $submission->addMedia($file)->toMediaCollection('document');
        }

        return $submission;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $submissionId, string $adminId): RequirementSubmission
    {
        $submission = RequirementSubmission::findOrFail($submissionId);

        $submission->update([
            'status' => SubmissionStatus::VERIFIED,
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
    ): RequirementSubmission {
        $submission = RequirementSubmission::findOrFail($submissionId);

        $submission->update([
            'status' => SubmissionStatus::REJECTED,
            'notes' => $notes,
            'verified_at' => null,
            'verified_by' => $adminId,
        ]);

        return $submission;
    }

    /**
     * {@inheritdoc}
     */
    public function hasClearedMandatory(string $registrationId): bool
    {
        $registration = app(RegistrationService::class)->find($registrationId);

        if (!$registration) {
            return false;
        }

        $mandatoryRequirements = $this->getActiveForYear($registration->academic_year)->where(
            'is_mandatory',
            true,
        );

        if ($mandatoryRequirements->isEmpty()) {
            return true;
        }

        $verifiedCount = RequirementSubmission::query()
            ->where('registration_id', $registrationId)
            ->whereIn('requirement_id', $mandatoryRequirements->pluck('id'))
            ->where('status', SubmissionStatus::VERIFIED)
            ->count();

        return $verifiedCount === $mandatoryRequirements->count();
    }
}
