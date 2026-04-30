<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

/**
 * Stateless Action to submit an assignment.
 *
 * S1 - Secure: Validates submission eligibility.
 * S2 - Sustain: Creates submission with media support.
 */
class SubmitAssignmentAction
{
    public function execute(
        Assignment $assignment,
        string $registrationId,
        string $studentId,
        ?string $content = null,
        ?string $mediaPath = null,
    ): Submission {
        if ($assignment->status !== AssignmentStatus::PUBLISHED) {
            throw new \InvalidArgumentException('Cannot submit to unpublished assignment.');
        }

        if ($assignment->isOverdue()) {
            throw new \InvalidArgumentException('Assignment is overdue.');
        }

        return DB::transaction(function () use ($assignment, $registrationId, $studentId, $content, $mediaPath) {
            $submission = Submission::create([
                'assignment_id' => $assignment->id,
                'registration_id' => $registrationId,
                'student_id' => $studentId,
                'content' => $content,
                'submitted_at' => now(),
                'status' => SubmissionStatus::SUBMITTED,
            ]);

            if ($mediaPath) {
                $submission->addMedia($mediaPath)
                    ->toMediaCollection('file');
            }

            return $submission;
        });
    }
}
