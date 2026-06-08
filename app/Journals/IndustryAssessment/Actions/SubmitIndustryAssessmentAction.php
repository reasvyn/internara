<?php

declare(strict_types=1);

namespace App\Journals\IndustryAssessment\Actions;

use App\Core\Actions\BaseAction;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\IndustryAssessment\Models\IndustryAssessment;
use App\User\Models\User;

final class SubmitIndustryAssessmentAction extends BaseAction
{
    /**
     * Submit or update an industry supervisor's final assessment for a student.
     *
     * @param array<int, array{criterion: string, weight: numeric, score: numeric}> $rubricData
     */
    public function execute(
        Registration $registration,
        User $supervisor,
        ?array $rubricData,
        ?string $notes,
    ): IndustryAssessment {
        return $this->transaction(function () use (
            $registration,
            $supervisor,
            $rubricData,
            $notes,
        ) {
            $score = null;
            if ($rubricData !== null && $rubricData !== []) {
                $totalWeight = array_sum(array_column($rubricData, 'weight'));
                $weightedSum = 0;
                foreach ($rubricData as $criterion) {
                    $weightedSum += (float) $criterion['weight'] * (float) $criterion['score'];
                }
                $score = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;
            }

            $assessment = IndustryAssessment::updateOrCreate(
                [
                    'registration_id' => $registration->id,
                    'supervisor_id' => $supervisor->id,
                ],
                [
                    'score' => $score,
                    'rubric_data' => $rubricData,
                    'notes' => $notes,
                    'submitted_at' => now(),
                ],
            );

            $this->log('industry_assessment_submitted', $assessment, [
                'registration_id' => $registration->id,
                'supervisor_id' => $supervisor->id,
                'score' => $score,
            ]);

            return $assessment;
        });
    }
}
