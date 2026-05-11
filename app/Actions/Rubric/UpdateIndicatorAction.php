<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Models\Indicator;

class UpdateIndicatorAction
{
    public function execute(
        Indicator $indicator,
        string $name,
        ?string $description = null,
        int $maxScore = 100,
        int $weight = 0,
        int $order = 0,
    ): Indicator {
        $indicator->update([
            'name' => $name,
            'description' => $description,
            'max_score' => $maxScore,
            'weight' => $weight,
            'order' => $order,
        ]);

        return $indicator->fresh();
    }
}
