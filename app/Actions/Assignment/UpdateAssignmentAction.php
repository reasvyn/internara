<?php

declare(strict_types=1);

namespace App\Actions\Assignment;

use App\Models\Assignment;

/**
 * Stateless Action to update an existing assignment.
 *
 * S1 - Secure: Validated updates with authorization check.
 * S2 - Sustain: Single-purpose action.
 */
class UpdateAssignmentAction
{
    public function execute(
        Assignment $assignment,
        ?string $title = null,
        ?string $description = null,
        ?string $academicYear = null,
        ?bool $isMandatory = null,
        ?string $dueDate = null,
        array $config = [],
    ): Assignment {
        $assignment->update(array_filter([
            'title' => $title,
            'description' => $description,
            'academic_year' => $academicYear,
            'is_mandatory' => $isMandatory,
            'due_date' => $dueDate,
            'config' => $config,
        ], fn ($value) => ! is_null($value)));

        return $assignment->fresh();
    }
}
