<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Data\CreateRubricData;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;

final class CreateRubricAction extends BaseCommandAction
{
    public function execute(CreateRubricData $data): ActionResponse
    {
        return $this->transaction(function () use ($data) {
            $rubric = Rubric::create([
                'name' => $data->name,
                'description' => $data->description,
                'is_active' => $data->isActive,
                'created_by' => auth()->id(),
            ]);

            $this->log('rubric_created', $rubric, ['name' => $rubric->name]);

            return ActionResponse::created($rubric);
        });
    }
}
