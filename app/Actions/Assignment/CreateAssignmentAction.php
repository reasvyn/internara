<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Models\Assignment;
use App\Models\AssignmentType;

/**
 * Stateless Action to create a new assignment.
 *
 * S1 - Secure: Validated creation with type verification.
 * S2 - Sustain: Clear single-purpose action.
 */
class CreateAssignmentAction
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

        return $assignment;
    }
}
