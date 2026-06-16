<?php

declare(strict_types=1);

namespace App\Assignment\Actions;

use App\Assignment\Models\Assignment;
use App\Core\Actions\BaseCommandAction;

final class UpdateAssignmentAction extends BaseCommandAction
{
    public function execute(
        Assignment $assignment,
        ?string $assignmentType = null,
        ?string $title = null,
        ?string $description = null,
        ?bool $isMandatory = null,
        ?string $dueDate = null,
    ): Assignment {
        return $this->transaction(function () use ($assignment, $assignmentType, $title, $description, $isMandatory, $dueDate) {
            $assignment->update(
                array_filter(
                    [
                        'assignment_type' => $assignmentType,
                        'title' => $title,
                        'description' => $description,
                        'is_mandatory' => $isMandatory,
                        'due_date' => $dueDate,
                    ],
                    fn ($value) => ! is_null($value),
                ),
            );

            $this->log('assignment_updated', $assignment, ['title' => $assignment->title]);

            return $assignment->fresh();
        });
    }
}
