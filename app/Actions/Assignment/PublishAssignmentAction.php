<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;

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

        return $assignment->fresh();
    }
}
