<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;

final class ScoreIndicatorAction extends BaseCommandAction
{
    public function execute(
        Assessment $assessment,
        Rubric $rubric,
        string $competencyId,
        string $indicatorId,
        float $score,
        User $evaluator,
    ): Assessment {
        if ($assessment->finalized_at !== null) {
            throw new RejectedException('Cannot modify a finalized assessment.');
        }

        $structure = $rubric->structure;
        $competency = null;
        $indicator = null;

        foreach ($structure['competencies'] as $c) {
            if ($c['id'] === $competencyId) {
                $competency = $c;
                foreach ($c['indicators'] as $i) {
                    if ($i['id'] === $indicatorId) {
                        $indicator = $i;
                        break 2;
                    }
                }
            }
        }

        if ($competency === null || $indicator === null) {
            throw new RejectedException('Competency or indicator not found.');
        }

        $this->ensureAuthorized($assessment, $competency, $evaluator);

        if ($score < 0 || $score > $indicator['max_score']) {
            throw new RejectedException("Score must be between 0 and {$indicator['max_score']}.");
        }

        $scoresData = $assessment->scores_data ?? [];
        $scoresData['competencies'] ??= [];

        $found = false;
        foreach ($scoresData['competencies'] as &$compData) {
            if (($compData['id'] ?? null) === $competencyId) {
                $compData['indicators'][$indicatorId] = $score;
                $compData['evaluator_id'] = $evaluator->id;
                $compData['evaluated_at'] = now()->toIso8601String();
                $found = true;
                break;
            }
        }

        if (! $found) {
            $scoresData['competencies'][] = [
                'id' => $competencyId,
                'evaluator_id' => $evaluator->id,
                'evaluated_at' => now()->toIso8601String(),
                'indicators' => [
                    $indicatorId => $score,
                ],
            ];
        }

        $assessment->update(['scores_data' => $scoresData]);

        return $assessment->fresh();
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
            throw new RejectedException('You are not authorized to score this competency.');
        }

        $isAssignedToRegistration = $assessment
            ->registration
            ->mentors()
            ->where('user_id', $evaluator->id)
            ->where('internship_group_members.role', $allowedRole)
            ->exists();

        if (! $isAssignedToRegistration) {
            throw new RejectedException('You are not assigned as a mentor for this registration.');
        }
    }
}
