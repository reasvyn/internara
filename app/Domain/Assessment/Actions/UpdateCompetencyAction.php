<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Enums\EvaluatorRole;
use App\Domain\Assessment\Models\Competency;
use App\Domain\Core\Actions\BaseAction;

class UpdateCompetencyAction extends BaseAction
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
