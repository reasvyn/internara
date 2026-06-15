<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;

final class DeleteCompetencyAction extends BaseCommandAction
{
    public function execute(Rubric $rubric, string $competencyId): void
    {
        $this->transaction(function () use ($rubric, $competencyId) {
            $structure = $rubric->structure;

            $structure['competencies'] = array_values(
                array_filter($structure['competencies'], fn (array $c) => $c['id'] !== $competencyId)
            );

            $rubric->update(['structure' => $structure]);

            $this->log('competency_deleted', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $competencyId,
            ]);
        });
    }
}
