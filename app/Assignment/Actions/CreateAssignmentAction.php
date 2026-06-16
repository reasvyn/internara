<?php

declare(strict_types=1);

namespace App\Assignment\Actions;

use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Core\Actions\BaseCommandAction;

final class CreateAssignmentAction extends BaseCommandAction
{
    public function execute(
        string $assignmentType,
        string $internshipId,
        string $title,
        ?string $description = null,
        bool $isMandatory = false,
        ?string $dueDate = null,
    ): Assignment {
        return $this->transaction(function () use (
            $assignmentType, $internshipId, $title, $description,
            $isMandatory, $dueDate,
        ) {
            $assignment = Assignment::create([
                'assignment_type' => $assignmentType,
                'internship_id' => $internshipId,
                'title' => $title,
                'description' => $description,
                'is_mandatory' => $isMandatory,
                'due_date' => $dueDate,
                'status' => AssignmentStatus::DRAFT->value,
                'created_by' => auth()->id(),
            ]);

            $this->log('assignment_created', $assignment, ['title' => $assignment->title]);

            return $assignment;
        });
    }
}
