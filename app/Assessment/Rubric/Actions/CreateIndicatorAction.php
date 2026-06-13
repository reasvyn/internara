<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseAction;
use Illuminate\Support\Str;

final class CreateIndicatorAction extends BaseAction
{
    public function execute(
        Rubric $rubric,
        string $competencyId,
        string $name,
        ?string $description = null,
        int $maxScore = 100,
        int $weight = 0,
        int $order = 0,
    ): Rubric {
        return $this->transaction(function () use ($rubric, $competencyId, $name, $description, $maxScore, $weight, $order) {
            $structure = $rubric->structure;

            $competencies = &$structure['competencies'];
            foreach ($competencies as &$competency) {
                if ($competency['id'] === $competencyId) {
                    $competency['indicators'][] = [
                        'id' => (string) Str::uuid(),
                        'name' => $name,
                        'description' => $description,
                        'max_score' => $maxScore,
                        'weight' => $weight,
                        'order' => $order,
                    ];
                    break;
                }
            }

            $rubric->update(['structure' => $structure]);

            $this->log('indicator_created', $rubric, [
                'rubric_id' => $rubric->id,
                'competency_id' => $competencyId,
                'indicator_name' => $name,
            ]);

            return $rubric;
        });
    }
}
