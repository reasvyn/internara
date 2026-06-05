<?php

declare(strict_types=1);

namespace App\Assignment\Assignment\Actions;

use App\Assignment\Assignment\Models\Assignment;
use App\Assignment\Assignment\Models\AssignmentType;
use App\Core\Actions\BaseAction;

/**
 * Stateless Action to create a new assignment.
 *
 * S1 - Secure: Validated creation with type verification.
 * S2 - Sustain: Clear single-purpose action.
 */
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
            'status' => 'draft',
        ]);

        $this->log('assignment_created', $assignment, ['title' => $assignment->title]);

        return $assignment;
    }
}
