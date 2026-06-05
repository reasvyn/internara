<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Competency;
use App\Core\Actions\BaseAction;
use App\Evaluation\Core\Enums\EvaluatorRole;

final class CreateCompetencyAction extends BaseAction
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
