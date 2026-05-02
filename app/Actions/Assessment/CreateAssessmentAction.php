<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Models\Assessment;
use App\Models\InternshipRegistration;
use App\Models\User;
use InvalidArgumentException;

/**
 * Stateless Action to create a new assessment.
 *
 * S1 - Secure: Validated creation with evaluator authorization.
 * S2 - Sustain: Single-purpose action.
 */
class CreateAssessmentAction
{
    public function execute(
        User $evaluator,
        string $registrationId,
        ?string $academicYear = null,
        ?string $type = 'formative',
        ?array $content = null,
        ?float $score = null,
    ): Assessment {
        if (! $evaluator->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            throw new InvalidArgumentException('Not authorized to create assessments.');
        }

        $registration = InternshipRegistration::findOrFail($registrationId);

        $assessment = Assessment::create([
            'registration_id' => $registration->id,
            'academic_year' => $academicYear ?? now()->format('Y'),
            'evaluator_id' => $evaluator->id,
            'type' => $type,
            'score' => $score,
            'content' => $content,
        ]);

        return $assessment;
    }
}
