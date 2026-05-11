<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Enums\Assessment\EvaluatorRole;
use App\Models\Competency;

class UpdateCompetencyAction
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
