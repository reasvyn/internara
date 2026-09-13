<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Data\ScoreIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;

final class ScoreIndicatorAction extends BaseCommandAction
{
    public function execute(
        Assessment $assessment,
        Rubric $rubric,
        ScoreIndicatorData $data,
        User $evaluator,
    ): ActionResponse {
        if ($assessment->finalized_at !== null) {
            throw new RejectedException(__('assessment.cannot_modify_finalized'));
        }

        [$competency, $indicator] = $this->findIndicator($rubric, $data);
        $this->ensureAuthorized($assessment, $competency, $evaluator);

        if ($data->score < 0 || $data->score > $indicator['max_score']) {
            throw new RejectedException("Score must be between 0 and {$indicator['max_score']}.");
        }

        return $this->transaction(function () use ($assessment, $data, $evaluator): ActionResponse {
            $assessment->update([
                'scores_data' => $this->recordScore($assessment, $data, $evaluator),
            ]);

            $this->log('indicator_scored', $assessment, [
                'competency_id' => $data->competencyId,
                'indicator_id' => $data->indicatorId,
                'score' => $data->score,
            ]);

            return ActionResponse::updated($assessment->fresh());
        });
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function findIndicator(Rubric $rubric, ScoreIndicatorData $data): array
    {
        foreach ($rubric->structure['competencies'] as $competency) {
            if ($competency['id'] !== $data->competencyId) {
                continue;
            }

            foreach ($competency['indicators'] as $indicator) {
                if ($indicator['id'] === $data->indicatorId) {
                    return [$competency, $indicator];
                }
            }
        }

        throw new RejectedException(__('assessment.not_found'));
    }

    /**
     * @return array<string, mixed>
     */
    private function recordScore(
        Assessment $assessment,
        ScoreIndicatorData $data,
        User $evaluator,
    ): array {
        $scoresData = $assessment->scores_data ?? [];
        $scoresData['competencies'] ??= [];
        $evaluatedAt = now()->toIso8601String();

        foreach ($scoresData['competencies'] as &$competency) {
            if (($competency['id'] ?? null) !== $data->competencyId) {
                continue;
            }

            $competency['indicators'][$data->indicatorId] = $data->score;
            $competency['evaluator_id'] = $evaluator->id;
            $competency['evaluated_at'] = $evaluatedAt;

            return $scoresData;
        }

        $scoresData['competencies'][] = [
            'id' => $data->competencyId,
            'evaluator_id' => $evaluator->id,
            'evaluated_at' => $evaluatedAt,
            'indicators' => [$data->indicatorId => $data->score],
        ];

        return $scoresData;
    }

    private function ensureAuthorized(
        Assessment $assessment,
        array $competency,
        User $evaluator,
    ): void {
        if ($evaluator->hasRole('super_admin') || $evaluator->hasRole('admin')) {
            return;
        }

        $allowedRole = $competency['evaluator_role'];

        if (! $evaluator->hasRole($allowedRole)) {
            throw new RejectedException(__('assessment.not_authorized'));
        }

        $isAssignedToRegistration = $assessment
            ->registration
            ->mentors()
            ->where('user_id', $evaluator->id)
            ->where('internship_group_members.role', $allowedRole)
            ->exists();

        if (! $isAssignedToRegistration) {
            throw new RejectedException(__('assessment.not_assigned_mentor'));
        }
    }
}
