<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Data\DeleteIndicatorData;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;

final class DeleteIndicatorAction extends BaseCommandAction
{
    public function execute(
        Rubric $rubric,
        DeleteIndicatorData $data,
    ): ActionResponse {
        $this->transaction(function () use ($rubric, $data) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $data->competencyId) {
                    $competency['indicators'] = array_values(
                        array_filter($competency['indicators'], fn (array $i) => $i['id'] !== $data->indicatorId)
                    );
                    break;
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('indicator_deleted', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $data->competencyId,
                'indicator_id' => $data->indicatorId,
            ]);
        });

        return ActionResponse::deleted();
    }
}
