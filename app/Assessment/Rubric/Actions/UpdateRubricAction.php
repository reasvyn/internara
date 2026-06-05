<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseAction;

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
