<?php

declare(strict_types=1);

namespace App\Assignment\Actions;

use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Events\AssignmentPublished;
use App\Assignment\Models\Assignment;
use App\Assignment\Notifications\AssignmentNotification;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Support\Facades\Notification;

final class PublishAssignmentAction extends BaseCommandAction
{
    public function execute(Assignment $assignment): Assignment
    {
        if ($assignment->status !== AssignmentStatus::DRAFT) {
            throw new RejectedException('Only draft assignments can be published.');
        }

        return $this->transaction(function () use ($assignment) {
            $assignment->update(['status' => AssignmentStatus::PUBLISHED->value]);

            $this->log('assignment_published', $assignment, ['title' => $assignment->title]);

            event(new AssignmentPublished($assignment));

            $students = Registration::where('internship_id', $assignment->internship_id)
                ->with('student')
                ->get()
                ->pluck('student');

            Notification::send($students, new AssignmentNotification(
                internshipName: $assignment->internship?->name ?? 'Unknown',
                assignmentTitle: $assignment->title,
                dueDate: $assignment->due_date?->toDateString(),
            ));

            return $assignment;
        });
    }
}
