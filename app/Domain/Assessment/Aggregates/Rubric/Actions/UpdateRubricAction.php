<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Aggregates\Rubric\Actions;

use App\Domain\Assessment\Aggregates\Rubric\Models\Rubric;
use App\Domain\Core\Actions\BaseAction;

final class UpdateRubricAction extends BaseAction
{
    public function execute(Rubric $rubric, string $name, ?string $description = null, bool $isActive = true): Rubric
    {
        $rubric->update([
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive,
        ]);

        return $rubric->fresh();
    }
}
