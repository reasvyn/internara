<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Actions;

use App\Modules\Assessment\Domain\Rubric\Data\UpdateIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;

final class UpdateIndicatorAction extends BaseCommandAction
{
    public function execute(
        Rubric $rubric,
        UpdateIndicatorData $data,
    ): ActionResponse {
        return $this->transaction(function () use ($rubric, $data) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $data->competencyId) {
                    foreach ($competency['indicators'] as &$indicator) {
                        if ($indicator['id'] === $data->indicatorId) {
                            $indicator['name'] = $data->name;
                            $indicator['description'] = $data->description;
                            $indicator['max_score'] = $data->maxScore;
                            $indicator['weight'] = $data->weight;
                            $indicator['order'] = $data->order;
                            break 2;
                        }
                    }
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('indicator_updated', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $data->competencyId,
                'indicator_id' => $data->indicatorId,
                'indicator_name' => $data->name,
            ]);

            return ActionResponse::updated($rubric);
        });
    }
}
