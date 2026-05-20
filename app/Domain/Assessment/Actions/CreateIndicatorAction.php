<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Indicator;
use App\Domain\Core\Actions\BaseAction;

class CreateIndicatorAction extends BaseAction
{
    public function execute(
        string $competencyId,
        string $name,
        ?string $description = null,
        int $maxScore = 100,
        int $weight = 0,
        int $order = 0,
    ): Indicator {
        return Indicator::create([
            'competency_id' => $competencyId,
            'name' => $name,
            'description' => $description,
            'max_score' => $maxScore,
            'weight' => $weight,
            'order' => $order,
        ]);
    }
}
