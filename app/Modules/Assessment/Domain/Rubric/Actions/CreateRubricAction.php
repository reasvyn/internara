<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Actions;

use App\Modules\Assessment\Domain\Rubric\Data\CreateRubricData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;

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
