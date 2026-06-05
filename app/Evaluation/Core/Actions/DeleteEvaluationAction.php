<?php

declare(strict_types=1);

namespace App\Evaluation\Core\Actions;

use App\Core\Actions\BaseAction;
use App\Evaluation\Core\Models\Evaluation;

final class DeleteEvaluationAction extends BaseAction
{
    public function execute(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }
}
