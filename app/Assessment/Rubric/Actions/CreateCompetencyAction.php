<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseAction;
use Illuminate\Support\Str;

final class CreateCompetencyAction extends BaseAction
{
    public function execute(
        Rubric $rubric,
        string $name,
        ?string $description = null,
        int $weight = 0,
        string $evaluatorRole = 'teacher',
        int $order = 0,
    ): Rubric {
        return $this->transaction(function () use ($rubric, $name, $description, $weight, $evaluatorRole, $order) {
            $structure = $rubric->structure ?? ['competencies' => []];
            $structure['competencies'][] = [
                'id' => (string) Str::uuid(),
                'name' => $name,
                'description' => $description,
                'weight' => $weight,
                'evaluator_role' => $evaluatorRole,
                'order' => $order,
                'indicators' => [],
            ];

            $rubric->update(['structure' => $structure]);

            $this->log('competency_created', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_name' => $name,
            ]);

            return $rubric;
        });
    }
}
