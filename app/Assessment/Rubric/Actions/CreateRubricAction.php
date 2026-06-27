<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;

final class CreateRubricAction extends BaseCommandAction
{
    public function execute(
        string $name,
        ?string $description = null,
        bool $isActive = true,
    ): Rubric {
        return $this->transaction(function () use ($name, $description, $isActive) {
            $rubric = Rubric::create([
                'name' => $name,
                'description' => $description,
                'is_active' => $isActive,
                'created_by' => auth()->id(),
            ]);

            $this->log('rubric_created', $rubric, ['name' => $rubric->name]);

            return $rubric;
        });
    }
}
