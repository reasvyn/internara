<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Enums\Assessment\EvaluatorRole;
use App\Models\Competency;

class CreateCompetencyAction
{
    public function execute(
        string $rubricId,
        string $name,
        ?string $description = null,
        int $weight = 0,
        EvaluatorRole $evaluatorRole = EvaluatorRole::TEACHER,
        int $order = 0,
    ): Competency {
        return Competency::create([
            'rubric_id' => $rubricId,
            'name' => $name,
            'description' => $description,
            'weight' => $weight,
            'evaluator_role' => $evaluatorRole->value,
            'order' => $order,
        ]);
    }
}
