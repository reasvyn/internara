<?php

declare(strict_types=1);

namespace App\Assignment\Actions;

use App\Assignment\Data\UpdateAssignmentData;
use App\Assignment\Models\Assignment;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;

final class UpdateAssignmentAction extends BaseCommandAction
{
    public function execute(
        Assignment $assignment,
        UpdateAssignmentData $data,
    ): ActionResponse {
        return $this->transaction(function () use ($assignment, $data) {
            $assignment->update(
                array_filter(
                    [
                        'assignment_type' => $data->assignmentType,
                        'title' => $data->title,
                        'description' => $data->description,
                        'is_mandatory' => $data->isMandatory,
                        'due_date' => $data->dueDate,
                    ],
                    fn ($value) => ! is_null($value),
                ),
            );

            $this->log('assignment_updated', $assignment, ['title' => $assignment->title]);

            return ActionResponse::updated($assignment->fresh());
        });
    }
}
