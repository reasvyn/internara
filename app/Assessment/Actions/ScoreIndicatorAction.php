<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Indicator;
use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Guidance\Mentor\Models\Mentor;
use App\User\Models\User;

final class ScoreIndicatorAction extends BaseAction
{
    public function execute(Assessment $assessment, string $indicatorId, float $score, User $evaluator): Assessment
    {
        if ($assessment->finalized_at !== null) {
            throw new RejectedException('Cannot modify a finalized assessment.');
        }

        $indicator = Indicator::with('competency')->findOrFail($indicatorId);

        $this->ensureAuthorized($assessment, $indicator, $evaluator);

        if ($score < 0 || $score > $indicator->max_score) {
            throw new RejectedException("Score must be between 0 and {$indicator->max_score}.");
        }

        $content = $assessment->content ?? [];
        $competencyId = $indicator->competency_id;

        $content['competencies'][$competencyId]['evaluator_id'] = $evaluator->id;
        $content['competencies'][$competencyId]['evaluated_at'] = now()->toIso8601String();
        $content['competencies'][$competencyId]['indicators'][$indicatorId] = $score;

        $assessment->update(['content' => $content]);

        return $assessment->fresh();
    }

    private function ensureAuthorized(Assessment $assessment, Indicator $indicator, User $evaluator): void
    {
        if ($evaluator->hasRole('super_admin') || $evaluator->hasRole('admin')) {
            return;
        }

        $allowedRole = $indicator->competency->evaluator_role->value;

        if (! $evaluator->hasRole($allowedRole)) {
            throw new RejectedException('You are not authorized to score this competency.');
        }

        $mentorType = $allowedRole === 'teacher'
            ? Mentor::TYPE_SCHOOL_TEACHER
            : Mentor::TYPE_INDUSTRY_SUPERVISOR;

        $isAssignedToRegistration = Mentor::where('user_id', $evaluator->id)
            ->where('type', $mentorType)
            ->whereHas('registrations', fn ($q) => $q->where('registration_id', $assessment->registration_id))
            ->exists();

        if (! $isAssignedToRegistration) {
            throw new RejectedException('You are not assigned as a mentor for this registration.');
        }
    }
}
