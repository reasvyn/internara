<?php

declare(strict_types=1);

namespace App\Evaluation\Actions;

use App\Core\Actions\BaseAction;
use App\Evaluation\Enums\EvaluationCategory;
use App\Evaluation\Models\Evaluation;
use App\User\Models\User;

final class SubmitEvaluationAction extends BaseAction
{
    public function execute(
        User $evaluator,
        EvaluationCategory $type,
        array $data,
        ?Evaluation $existing = null,
    ): Evaluation {
        return $this->transaction(function () use ($evaluator, $type, $data, $existing) {
            $payload = [
                'evaluation_type' => $type,
                'overall_score' => $data['overall_score'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'criteria_scores' => $data['criteria_scores'] ?? [],
            ];

            if ($type === EvaluationCategory::MENTOR && isset($data['mentor_id'])) {
                $payload['mentor_id'] = $data['mentor_id'];
            }

            if (isset($data['target_type'], $data['target_id'])) {
                $payload['target_type'] = $data['target_type'];
                $payload['target_id'] = $data['target_id'];
            }

            if (isset($data['registration_id'])) {
                $payload['registration_id'] = $data['registration_id'];
            }

            if ($existing) {
                $existing->update($payload);
                $this->log("evaluation_{$type->value}_updated", $existing);

                return $existing;
            }

            $evaluation = Evaluation::create(
                array_merge($payload, ['evaluator_id' => $evaluator->id]),
            );

            $this->log("evaluation_{$type->value}_created", $evaluation);

            return $evaluation;
        });
    }
}
