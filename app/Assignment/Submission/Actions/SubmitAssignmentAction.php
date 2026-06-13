<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Actions;

use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;

final class SubmitAssignmentAction extends BaseAction
{
    public function execute(User $student, Assignment $assignment, array $data): Submission
    {
        if ($assignment->status !== AssignmentStatus::PUBLISHED) {
            throw new RejectedException('Cannot submit to unpublished assignment.');
        }

        if ($assignment->asAssignmentRules()->isOverdue(now())) {
            throw new RejectedException('Assignment is overdue.');
        }

        return $this->transaction(function () use ($student, $assignment, $data) {
            $registration = Registration::where('student_id', $student->id)
                ->where('internship_id', $assignment->internship_id)
                ->whereIn('status', ['active', 'placed'])
                ->first();

            if (! $registration) {
                throw new RejectedException('No active registration found for this assignment.');
            }

            $existing = Submission::where('student_id', $student->id)
                ->where('assignment_id', $assignment->id)
                ->whereIn('status', [
                    SubmissionStatus::DRAFT->value,
                    SubmissionStatus::SUBMITTED->value,
                    SubmissionStatus::VERIFIED->value,
                ])
                ->first();

            if ($existing) {
                throw new RejectedException('You have already submitted this assignment.');
            }

            $submission = Submission::create([
                'student_id' => $student->id,
                'registration_id' => $registration->id,
                'assignment_id' => $assignment->id,
                'content' => $data['content'],
                'status' => SubmissionStatus::SUBMITTED->value,
                'submitted_at' => now(),
            ]);

            $this->log('assignment_submitted', $submission, [
                'assignment_title' => $assignment->title,
                'user_id' => $student->id,
            ]);

            return $submission;
        });
    }
}
