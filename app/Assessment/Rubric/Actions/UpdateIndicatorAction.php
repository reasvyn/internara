<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseAction;

final class UpdateIndicatorAction extends BaseAction
{
    public function execute(
        Rubric $rubric,
        string $competencyId,
        string $indicatorId,
        string $name,
        ?string $description = null,
        int $maxScore = 100,
        int $weight = 0,
        int $order = 0,
    ): Rubric {
        return $this->transaction(function () use ($rubric, $competencyId, $indicatorId, $name, $description, $maxScore, $weight, $order) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $competencyId) {
                    foreach ($competency['indicators'] as &$indicator) {
                        if ($indicator['id'] === $indicatorId) {
                            $indicator['name'] = $name;
                            $indicator['description'] = $description;
                            $indicator['max_score'] = $maxScore;
                            $indicator['weight'] = $weight;
                            $indicator['order'] = $order;
                            break 2;
                        }
                    }
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('indicator_updated', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $competencyId,
                'indicator_id' => $indicatorId,
                'indicator_name' => $name,
            ]);

            return $rubric;
        });
    }
}
