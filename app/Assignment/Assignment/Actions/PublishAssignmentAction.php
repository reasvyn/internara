<?php

declare(strict_types=1);

namespace App\Assignment\Assignment\Actions;

use App\Assignment\Assignment\Models\Assignment;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;

final class PublishAssignmentAction extends BaseAction
{
    public function execute(Assignment $assignment): Assignment
    {
        if ($assignment->status->value !== 'draft') {
            throw new RejectedException('Only draft assignments can be published.');
        }

        $assignment->update(['status' => 'published']);

        $this->log('assignment_published', $assignment, ['title' => $assignment->title]);

        return $assignment;
    }
}
