<?php

declare(strict_types=1);

namespace App\Evaluation\Evaluation\Actions;

use App\Core\Actions\BaseAction;
use App\Evaluation\Evaluation\Models\Evaluation;

final class DeleteEvaluationAction extends BaseAction
{
    public function execute(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }
}
