<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Data\UpdateRubricData;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;

final class UpdateRubricAction extends BaseCommandAction
{
    public function execute(
        Rubric $rubric,
        UpdateRubricData $data,
    ): ActionResponse {
        return $this->transaction(function () use ($rubric, $data) {
            $rubric->update([
                'name' => $data->name,
                'description' => $data->description,
                'is_active' => $data->isActive,
            ]);

            $this->log('rubric_updated', $rubric, ['name' => $data->name]);

            return ActionResponse::updated($rubric->fresh());
        });
    }
}
