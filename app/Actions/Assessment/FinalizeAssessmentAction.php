<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Actions\Core\LogAuditAction;
use App\Models\Assessment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinalizeAssessmentAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    public function execute(Assessment $assessment, User $finalizer): Assessment
    {
        return DB::transaction(function () use ($assessment, $finalizer) {
            if ($assessment->finalized_at !== null) {
                throw new RuntimeException('Assessment is already finalized.');
            }

            $rubric = $assessment->rubric;

            if ($rubric === null) {
                throw new RuntimeException('Assessment must have a rubric to finalize.');
            }

            $competencies = $rubric->competencies()->with('indicators')->get();
            $content = $assessment->content ?? [];
            $competencyScores = $content['competencies'] ?? [];

            // Phase 1: Separate scored and unscored competencies.
            // Competencies with evaluator_role = 'supervisor' that have no scores
            // are excluded; their weight is redistributed.
            $scoredCompetencies = [];

            foreach ($competencies as $competency) {
                $indicatorsData = $competencyScores[$competency->id]['indicators'] ?? [];
                $hasAnyScore = false;

                foreach ($competency->indicators as $indicator) {
                    if (($indicatorsData[$indicator->id] ?? null) !== null) {
                        $hasAnyScore = true;
                        break;
                    }
                }

                if (! $hasAnyScore && $competency->evaluator_role?->value === 'supervisor') {
                    continue;
                }

                $scoredCompetencies[] = $competency;
            }

            if (empty($scoredCompetencies)) {
                throw new RuntimeException('No competencies have been scored.');
            }

            // Phase 2: Redistribute weights proportionally.
            $originalTotalWeight = (int) $competencies->sum('weight');
            $scoredTotalWeight = (int) collect($scoredCompetencies)->sum('weight');

            if ($scoredTotalWeight === 0) {
                throw new RuntimeException('No competencies have been scored.');
            }

            $totalWeightedScore = 0.0;

            foreach ($scoredCompetencies as $competency) {
                $effectiveWeight = $originalTotalWeight > 0
                    ? ($competency->weight / $scoredTotalWeight) * $originalTotalWeight
                    : $competency->weight;

                $indicatorsData = $competencyScores[$competency->id]['indicators'] ?? [];
                $competencyScore = 0.0;
                $totalIndicatorWeight = 0;

                foreach ($competency->indicators as $indicator) {
                    $score = $indicatorsData[$indicator->id] ?? null;
                    if ($score !== null) {
                        $normalized = ($score / $indicator->max_score) * 100;
                        $competencyScore += $normalized * ($indicator->weight / 100);
                        $totalIndicatorWeight += $indicator->weight;
                    }
                }

                if ($totalIndicatorWeight > 0) {
                    $totalWeightedScore += $competencyScore * ($effectiveWeight / 100);
                }
            }

            $finalScore = round($totalWeightedScore, 1);

            $assessment->update([
                'score' => $finalScore,
                'finalized_at' => now(),
                'evaluator_id' => $finalizer->id,
                'content' => $content,
            ]);

            $this->logAuditAction->execute(
                action: 'assessment_finalized',
                subjectType: Assessment::class,
                subjectId: $assessment->id,
                payload: ['final_score' => $finalScore],
                module: 'Assessment',
            );

            return $assessment->fresh();
        });
    }
}
