<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Models\Rubric;

class UpdateRubricAction
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
