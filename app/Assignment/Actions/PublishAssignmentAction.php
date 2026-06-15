<?php

declare(strict_types=1);

namespace App\Assignment\Actions;

use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;

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

            return $assignment;
        });
    }
}
