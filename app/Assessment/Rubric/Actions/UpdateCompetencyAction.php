<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;

final class UpdateCompetencyAction extends BaseCommandAction
{
    public function execute(
        Rubric $rubric,
        string $competencyId,
        string $name,
        ?string $description = null,
        int $weight = 0,
        string $evaluatorRole = 'teacher',
        int $order = 0,
    ): Rubric {
        return $this->transaction(function () use ($rubric, $competencyId, $name, $description, $weight, $evaluatorRole, $order) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $competencyId) {
                    $competency['name'] = $name;
                    $competency['description'] = $description;
                    $competency['weight'] = $weight;
                    $competency['evaluator_role'] = $evaluatorRole;
                    $competency['order'] = $order;
                    break;
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('competency_updated', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $competencyId,
                'competency_name' => $name,
            ]);

            return $rubric;
        });
    }
}
