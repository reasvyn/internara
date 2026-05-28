<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Indicator;
use App\Domain\Core\Actions\BaseAction;

final class UpdateIndicatorAction extends BaseAction
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
