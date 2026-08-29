<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Actions;

use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Core\Actions\BaseCommandAction;

final class DeleteCompetencyAction extends BaseCommandAction
{
    public function execute(Rubric $rubric, string $competencyId): Rubric
    {
        return $this->transaction(function () use ($rubric, $competencyId) {
            $structure = $rubric->structure;

            $structure['competencies'] = array_values(
                array_filter($structure['competencies'], fn (array $c) => $c['id'] !== $competencyId)
            );

            $rubric->update(['structure' => $structure]);

            $this->log('competency_deleted', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $competencyId,
            ]);

            return $rubric->fresh();
        });
    }
}
