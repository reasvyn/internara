<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Models\Assessment;
use App\Models\Registration;
use App\Models\Rubric;

class InitializeAssessmentAction
{
    /**
     * Find or create an assessment for a registration.
     *
     * @return array{assessment: Assessment, rubric: ?Rubric}
     */
    public function execute(string $registrationId): array
    {
        $registration = Registration::with('internship')->findOrFail($registrationId);

        $rubric = Rubric::where('internship_id', $registration->internship_id)
            ->orWhereNull('internship_id')
            ->where('is_active', true)
            ->first();

        if ($rubric === null) {
            return ['assessment' => null, 'rubric' => null];
        }

        $assessment = Assessment::firstOrCreate(
            ['registration_id' => $registrationId],
            [
                'rubric_id' => $rubric->id,
                'type' => 'final',
            ],
        );

        return ['assessment' => $assessment, 'rubric' => $rubric];
    }
}
