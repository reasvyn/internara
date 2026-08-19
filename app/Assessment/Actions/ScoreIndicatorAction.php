<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Data\ScoreIndicatorData;
use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;

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

        $structure = $rubric->structure;
        $competency = null;
        $indicator = null;

        foreach ($structure['competencies'] as $c) {
            if ($c['id'] === $data->competencyId) {
                $competency = $c;
                foreach ($c['indicators'] as $i) {
                    if ($i['id'] === $data->indicatorId) {
                        $indicator = $i;
                        break 2;
                    }
                }
            }
        }

        if ($competency === null || $indicator === null) {
            throw new RejectedException(__('assessment.not_found'));
        }

        $this->ensureAuthorized($assessment, $competency, $evaluator);

        if ($data->score < 0 || $data->score > $indicator['max_score']) {
            throw new RejectedException("Score must be between 0 and {$indicator['max_score']}.");
        }

        $scoresData = $assessment->scores_data ?? [];
        $scoresData['competencies'] ??= [];

        $found = false;
        foreach ($scoresData['competencies'] as &$compData) {
            if (($compData['id'] ?? null) === $data->competencyId) {
                $compData['indicators'][$data->indicatorId] = $data->score;
                $compData['evaluator_id'] = $evaluator->id;
                $compData['evaluated_at'] = now()->toIso8601String();
                $found = true;
                break;
            }
        }

        if (! $found) {
            $scoresData['competencies'][] = [
                'id' => $data->competencyId,
                'evaluator_id' => $evaluator->id,
                'evaluated_at' => now()->toIso8601String(),
                'indicators' => [
                    $data->indicatorId => $data->score,
                ],
            ];
        }

        $assessment->update(['scores_data' => $scoresData]);

        $this->log('indicator_scored', $assessment, [
            'competency_id' => $data->competencyId,
            'indicator_id' => $data->indicatorId,
            'score' => $data->score,
        ]);

        return ActionResponse::updated($assessment->fresh());
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
