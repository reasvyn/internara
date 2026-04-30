<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipRegistration;
use App\Models\RequirementSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Atomic submission with file handling and auditing.
 * S3 - Scalable: Stateless action.
 */
class SubmitRequirementAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    /**
     * Execute the requirement submission.
     */
    public function execute(InternshipRegistration $registration, string $requirementId, mixed $value): RequirementSubmission
    {
        return DB::transaction(function () use ($registration, $requirementId, $value) {
            /** @var RequirementSubmission $submission */
            $submission = RequirementSubmission::updateOrCreate(
                [
                    'registration_id' => $registration->id,
                    'requirement_id' => $requirementId,
                ],
                [
                    'value' => is_string($value) ? $value : null,
                ]
            );

            // Handle file upload via Spatie Media Library
            if ($value instanceof UploadedFile) {
                $submission->addMedia($value)->toMediaCollection('document');
            }

            $submission->setStatus('pending', 'Submitted by student.');

            $this->logAuditAction->execute(
                action: 'requirement_submitted',
                subjectType: RequirementSubmission::class,
                subjectId: $submission->id,
                payload: [
                    'registration_id' => $registration->id,
                    'requirement_id' => $requirementId,
                    'is_file' => $value instanceof UploadedFile
                ],
                module: 'Internship'
            );

            return $submission;
        });
    }
}
