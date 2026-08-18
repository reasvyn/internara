<?php

declare(strict_types=1);

namespace App\Assignment\Actions;

use App\Assignment\Data\CreateAssignmentData;
use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;

final class CreateAssignmentAction extends BaseCommandAction
{
    public function execute(CreateAssignmentData $data): ActionResponse
    {
        return $this->transaction(function () use ($data) {
            $assignment = Assignment::create([
                'assignment_type' => $data->assignmentType,
                'internship_id' => $data->internshipId,
                'title' => $data->title,
                'description' => $data->description,
                'is_mandatory' => $data->isMandatory,
                'due_date' => $data->dueDate,
                'status' => AssignmentStatus::DRAFT->value,
                'created_by' => auth()->id(),
            ]);

            $this->log('assignment_created', $assignment, ['title' => $assignment->title]);

            return ActionResponse::created($assignment);
        });
    }
}
