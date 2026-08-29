<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Actions;

use App\Modules\Assignment\Models\Assignment;
use App\Modules\Core\Actions\BaseCommandAction;

/**
 * Stateless Action to delete an assignment.
 *
 * S1 - Secure: Cascades to submissions via DB constraint.
 * S2 - Sustain: Clean removal.
 */
final class DeleteAssignmentAction extends BaseCommandAction
{
    public function execute(Assignment $assignment): void
    {
        $this->transaction(function () use ($assignment) {
            $assignment->delete();

            $this->log('assignment_deleted', $assignment, ['title' => $assignment->title]);
        });
    }
}
