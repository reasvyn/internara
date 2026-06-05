<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Competency;
use App\Core\Actions\BaseAction;
use App\Evaluation\Core\Enums\EvaluatorRole;

final class UpdateCompetencyAction extends BaseAction
{
    public function execute(
        Competency $competency,
        string $name,
        ?string $description = null,
        int $weight = 0,
        EvaluatorRole $evaluatorRole = EvaluatorRole::TEACHER,
        int $order = 0,
    ): Competency {
        $competency->update([
            'name' => $name,
            'description' => $description,
            'weight' => $weight,
            'evaluator_role' => $evaluatorRole->value,
            'order' => $order,
        ]);

        return $competency->fresh();
    }
}
