<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Enrollment\Registration\Models\Registration;

final class InitializeAssessmentAction extends BaseCommandAction
{
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

        $assessment = $this->transaction(function () use ($registrationId, $rubric) {
            $assessment = Assessment::firstOrCreate(
                ['registration_id' => $registrationId],
                ['rubric_id' => $rubric->id],
            );

            $this->log('assessment_initialized', $assessment, [
                'registration_id' => $registrationId,
                'rubric_id' => $rubric->id,
            ]);

            return $assessment;
        });

        return ['assessment' => $assessment, 'rubric' => $rubric];
    }
}
