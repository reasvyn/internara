<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Actions;

use App\Modules\Assignment\Data\CreateAssignmentData;
use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;

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
