<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Actions;

use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\Submission;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\User\Models\User;

class SubmitAssignmentAction extends BaseAction
{
    public function execute(User $student, Assignment $assignment, array $data): Submission
    {
        if ($assignment->status->value !== 'published') {
            throw new RejectedException('Cannot submit to unpublished assignment.');
        }

        if ($assignment->asAssignmentRules()->isOverdue(now())) {
            throw new RejectedException('Assignment is overdue.');
        }

        return $this->transaction(function () use ($student, $assignment, $data) {
            $mentee = Mentee::where('user_id', $student->id)->first();
            $registration = $mentee?->registrations()
                ->where('internship_id', $assignment->internship_id)
                ->whereIn('status', ['active', 'placed'])
                ->first();

            if (! $registration) {
                throw new RejectedException('No active registration found for this assignment.');
            }

            $existing = Submission::where('student_id', $student->id)
                ->where('assignment_id', $assignment->id)
                ->whereIn('status', ['draft', 'submitted', 'verified'])
                ->first();

            if ($existing) {
                throw new RejectedException('You have already submitted this assignment.');
            }

            $submission = Submission::create([
                'student_id' => $student->id,
                'registration_id' => $registration->id,
                'assignment_id' => $assignment->id,
                'content' => $data['content'],
                'status' => 'submitted',
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
