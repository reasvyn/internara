<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Aggregates\Rubric\Actions;

use App\Domain\Assessment\Aggregates\Rubric\Models\Rubric;
use App\Domain\Core\Actions\BaseAction;

final class CreateRubricAction extends BaseAction
{
    public function execute(string $name, ?string $description = null, bool $isActive = true): Rubric
    {
        return Rubric::create([
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive,
            'created_by' => auth()->id(),
        ]);
    }
}
