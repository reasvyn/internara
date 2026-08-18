<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Data\CreateIndicatorData;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use Illuminate\Support\Str;

final class CreateIndicatorAction extends BaseCommandAction
{
    public function execute(
        Rubric $rubric,
        CreateIndicatorData $data,
    ): ActionResponse {
        return $this->transaction(function () use ($rubric, $data) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $data->competencyId) {
                    $competency['indicators'][] = [
                        'id' => (string) Str::uuid(),
                        'name' => $data->name,
                        'description' => $data->description,
                        'max_score' => $data->maxScore,
                        'weight' => $data->weight,
                        'order' => $data->order,
                    ];
                    break;
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('indicator_created', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $data->competencyId,
                'indicator_name' => $data->name,
            ]);

            return ActionResponse::created($rubric);
        });
    }
}
