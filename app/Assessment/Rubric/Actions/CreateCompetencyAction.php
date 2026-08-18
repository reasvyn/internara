<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Data\CreateCompetencyData;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use Illuminate\Support\Str;

final class CreateCompetencyAction extends BaseCommandAction
{
    public function execute(
        Rubric $rubric,
        CreateCompetencyData $data,
    ): ActionResponse {
        return $this->transaction(function () use ($rubric, $data) {
            $structure = $rubric->structure ?? ['competencies' => []];
            $structure['competencies'][] = [
                'id' => (string) Str::uuid(),
                'name' => $data->name,
                'description' => $data->description,
                'weight' => $data->weight,
                'evaluator_role' => $data->evaluatorRole,
                'order' => $data->order,
                'indicators' => [],
            ];

            $rubric->update(['structure' => $structure]);

            $this->log('competency_created', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_name' => $data->name,
            ]);

            return ActionResponse::created($rubric);
        });
    }
}
