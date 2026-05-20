<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Evaluation\Models\Evaluation;

class DeleteEvaluationAction extends BaseAction
{
    public function execute(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }
}
