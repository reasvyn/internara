<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Enums\AssignmentStatus;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Stateless Action to submit an assignment.
 *
 * S1 - Secure: Validates submission eligibility, prevents duplicate submissions.
 * S2 - Sustain: Creates submission with media support.
 */
class SubmitAssignmentAction
{
    public function execute(
        Assignment $assignment,
        string $registrationId,
        string $studentId,
        ?string $content = null,
        ?UploadedFile $file = null,
    ): Submission {
        if ($assignment->status !== AssignmentStatus::PUBLISHED) {
            throw new InvalidArgumentException('Cannot submit to unpublished assignment.');
        }

        if ($assignment->isOverdue()) {
            throw new InvalidArgumentException('Assignment is overdue.');
        }

        // Prevent duplicate submissions
        $existing = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->where('registration_id', $registrationId)
            ->exists();

        if ($existing) {
            throw new RuntimeException('You have already submitted this assignment.');
        }

        return DB::transaction(function () use ($assignment, $registrationId, $studentId, $content, $file) {
            $submission = Submission::create([
                'assignment_id' => $assignment->id,
                'registration_id' => $registrationId,
                'student_id' => $studentId,
                'content' => $content,
                'submitted_at' => now(),
                'status' => SubmissionStatus::SUBMITTED,
            ]);

            if ($file instanceof UploadedFile) {
                $submission->addMedia($file)->toMediaCollection('file');
            }

            return $submission;
        });
    }
}
