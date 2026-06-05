<?php

declare(strict_types=1);

namespace App\Assignment\Core\Actions;

use App\Assignment\Core\Models\Assignment;
use App\Core\Actions\BaseAction;

/**
 * Stateless Action to delete an assignment.
 *
 * S1 - Secure: Cascades to submissions via DB constraint.
 * S2 - Sustain: Clean removal.
 */
final class DeleteAssignmentAction extends BaseAction
{
    public function execute(Assignment $assignment): void
    {
        $assignment->delete();
    }
}
