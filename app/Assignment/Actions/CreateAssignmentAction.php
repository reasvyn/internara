<?php

declare(strict_types=1);

namespace App\Assignment\Actions;

use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Assignment\Models\AssignmentType;
use App\Core\Actions\BaseAction;

final class CreateAssignmentAction extends BaseAction
{
    public function execute(
        string $assignmentTypeId,
        string $internshipId,
        string $title,
        ?string $description = null,
        ?string $academicYear = null,
        bool $isMandatory = false,
        ?string $dueDate = null,
        array $config = [],
    ): Assignment {
        $type = AssignmentType::findOrFail($assignmentTypeId);

        return $this->transaction(function () use (
            $type, $internshipId, $title, $description, $academicYear,
            $isMandatory, $dueDate, $config,
        ) {
            $assignment = Assignment::create([
                'assignment_type_id' => $type->id,
                'internship_id' => $internshipId,
                'academic_year' => $academicYear,
                'title' => $title,
                'group' => $type->group,
                'description' => $description,
                'is_mandatory' => $isMandatory,
                'due_date' => $dueDate,
                'config' => $config,
                'status' => AssignmentStatus::DRAFT->value,
            ]);

            $this->log('assignment_created', $assignment, ['title' => $assignment->title]);

            return $assignment;
        });
    }
}
