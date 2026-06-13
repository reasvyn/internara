<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseAction;

final class DeleteIndicatorAction extends BaseAction
{
    public function execute(Rubric $rubric, string $competencyId, string $indicatorId): void
    {
        $this->transaction(function () use ($rubric, $competencyId, $indicatorId) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $competencyId) {
                    $competency['indicators'] = array_values(
                        array_filter($competency['indicators'], fn (array $i) => $i['id'] !== $indicatorId)
                    );
                    break;
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('indicator_deleted', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $competencyId,
                'indicator_id' => $indicatorId,
            ]);
        });
    }
}
