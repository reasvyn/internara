<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Actions;

use App\Modules\Assessment\Domain\Rubric\Data\UpdateCompetencyData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;

final class UpdateCompetencyAction extends BaseCommandAction
{
    public function execute(
        Rubric $rubric,
        UpdateCompetencyData $data,
    ): ActionResponse {
        return $this->transaction(function () use ($rubric, $data) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $data->competencyId) {
                    $competency['name'] = $data->name;
                    $competency['description'] = $data->description;
                    $competency['weight'] = $data->weight;
                    $competency['evaluator_role'] = $data->evaluatorRole;
                    $competency['order'] = $data->order;
                    break;
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('competency_updated', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $data->competencyId,
                'competency_name' => $data->name,
            ]);

            return ActionResponse::updated($rubric);
        });
    }
}
