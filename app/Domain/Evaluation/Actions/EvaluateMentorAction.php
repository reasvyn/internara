<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Evaluation\Models\Evaluation;
use App\Domain\User\Models\User;

class EvaluateMentorAction extends BaseAction
{
    public function execute(User $evaluator, User $mentor, array $data, ?Evaluation $existing = null): Evaluation
    {
        return $this->transaction(function () use ($evaluator, $mentor, $data, $existing) {
            if ($existing) {
                $existing->update([
                    'overall_score' => $data['overall_score'] ?? null,
                    'feedback' => $data['feedback'] ?? null,
                    'criteria_scores' => $data['criteria_scores'] ?? [],
                ]);

                $this->log('evaluation_updated', $existing);

                return $existing;
            }

            $evaluation = Evaluation::create([
                'evaluator_id' => $evaluator->id,
                'mentor_id' => $mentor->id,
                'overall_score' => $data['overall_score'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'criteria_scores' => $data['criteria_scores'] ?? [],
            ]);

            $this->log('evaluation_created', $evaluation);

            return $evaluation;
        });
    }
}
