<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Aggregates\Evaluation\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Evaluation\Aggregates\Evaluation\Models\Evaluation;

final class DeleteEvaluationAction extends BaseAction
{
    public function execute(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }
}
