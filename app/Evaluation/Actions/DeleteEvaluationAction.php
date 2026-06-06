<?php

declare(strict_types=1);

namespace App\Evaluation\Actions;

use App\Core\Actions\BaseAction;
use App\Evaluation\Models\Evaluation;

final class DeleteEvaluationAction extends BaseAction
{
    public function execute(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }
}
