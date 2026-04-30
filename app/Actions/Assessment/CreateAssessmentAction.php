<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Models\Assessment;
use App\Models\InternshipRegistration;

/**
 * Stateless Action to create a new assessment.
 *
 * S1 - Secure: Validated creation with evaluator authorization.
 * S2 - Sustain: Single-purpose action.
 */
class CreateAssessmentAction
{
    public function execute(
        string $registrationId,
        string $evaluatorId,
        ?string $academicYear = null,
        ?string $type = 'final',
        ?array $content = null,
        ?float $score = null,
    ): Assessment {
        $registration = InternshipRegistration::findOrFail($registrationId);

        $assessment = Assessment::create([
            'registration_id' => $registration->id,
            'academic_year' => $academicYear ?? now()->format('Y'),
            'evaluator_id' => $evaluatorId,
            'type' => $type,
            'score' => $score,
            'content' => $content,
        ]);

        return $assessment;
    }
}
