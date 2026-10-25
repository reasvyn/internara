<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Entities\AssessmentResult;
use App\Modules\Assessment\Events\AssessmentFinalized;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;

final class FinalizeAssessmentAction extends BaseCommandAction
{
    public function execute(Assessment $assessment, User $finalizer): Assessment
    {
        return $this->transaction(function () use ($assessment, $finalizer) {
            $this->ensureCanFinalize($assessment);

            $finalScore = AssessmentResult::fromModel($assessment)->calculateFinalScore(
                $assessment->rubric->structure['competencies'] ?? [],
            );

            $assessment->update([
                'score' => $finalScore,
                'finalized_at' => now(),
                'evaluator_id' => $finalizer->id,
            ]);

            $mentorEntity = $assessment->registration?->asMentorEntity();
            $proxyMeta = $mentorEntity !== null
                ? $mentorEntity->resolveProxyMetadata($finalizer, 'teacher')
                : [];

            $this->log('assessment_finalized', $assessment, array_merge([
                'final_score' => $finalScore,
            ], $proxyMeta), $finalizer);

            event(new AssessmentFinalized($assessment));

            return $assessment->fresh();
        });
    }

    private function ensureCanFinalize(Assessment $assessment): void
    {
        if ($assessment->finalized_at !== null) {
            throw new RejectedException(__('assessment.already_finalized'));
        }

        if ($assessment->rubric === null) {
            throw new RejectedException(__('assessment.rubric_required'));
        }
    }
}
