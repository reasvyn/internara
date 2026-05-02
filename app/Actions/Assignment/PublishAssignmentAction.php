<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Notifications\AssignmentNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Stateless Action to publish an assignment.
 *
 * S1 - Secure: Validates assignment can be published.
 * S2 - Sustain: Status transition logic in model.
 */
class PublishAssignmentAction
{
    public function execute(Assignment $assignment): Assignment
    {
        if ($assignment->status !== AssignmentStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft assignments can be published.');
        }

        $assignment->update(['status' => AssignmentStatus::PUBLISHED]);

        // Notify all students in this internship program
        $students = $assignment->internship->registrations()
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->pluck('student');

        if ($students->isNotEmpty()) {
            Notification::send($students, new AssignmentNotification(
                $assignment->internship->name,
                $assignment->title,
                $assignment->due_date?->format('d M Y')
            ));
        }

        return $assignment->fresh();
    }
}
