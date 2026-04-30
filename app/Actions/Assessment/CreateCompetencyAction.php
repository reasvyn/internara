<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Models\Competency;

/**
 * Stateless Action to create competency definition.
 *
 * S2 - Sustain: Reusable competency templates.
 */
class CreateCompetencyAction
{
    public function execute(
        string $departmentId,
        string $name,
        string $code,
        ?string $description = null,
        float $maxScore = 100.0,
        float $weight = 1.0,
    ): Competency {
        $competency = Competency::create([
            'department_id' => $departmentId,
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'max_score' => $maxScore,
            'weight' => $weight,
        ]);

        return $competency;
    }
}
