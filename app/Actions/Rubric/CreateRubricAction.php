<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Models\Rubric;

class CreateRubricAction
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
