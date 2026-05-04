<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Registration;
use App\Domain\Internship\Models\RequirementSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * S1 - Secure: Atomic submission with file validation, type restrictions, and auditing.
 * S3 - Scalable: Stateless action.
 */
class SubmitRequirementAction
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Execute the requirement submission.
     *
     * @throws RuntimeException if file type or size is invalid
     */
    public function execute(
        Registration $registration,
        string $requirementId,
        mixed $value,
    ): RequirementSubmission {
        return DB::transaction(function () use ($registration, $requirementId, $value) {
            if ($value instanceof UploadedFile) {
                $this->validateFile($value);
            }

            /** @var RequirementSubmission $submission */
            $submission = RequirementSubmission::updateOrCreate(
                [
                    'registration_id' => $registration->id,
                    'requirement_id' => $requirementId,
                ],
                [
                    'value' => is_string($value) ? $value : null,
                ],
            );

            // Handle file upload via Spatie Media Library
            if ($value instanceof UploadedFile) {
                $submission->clearMediaCollection('document');
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
                    'is_file' => $value instanceof UploadedFile,
                ],
                module: 'Internship',
            );

            return $submission;
        });
    }

    /**
     * Validate uploaded file type and size.
     */
    private function validateFile(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException(
                'File type not allowed. Allowed: PDF, JPEG, PNG, DOC, DOCX.',
            );
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new RuntimeException('File size exceeds maximum allowed (5MB).');
        }
    }
}
