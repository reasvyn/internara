<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Actions;

use App\Modules\Assessment\Domain\Rubric\Data\UpdateRubricData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;

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
