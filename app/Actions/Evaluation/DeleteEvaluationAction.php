<?php

declare(strict_types=1);

namespace App\Actions\Evaluation;

use App\Models\Evaluation;

class DeleteEvaluationAction
{
    public function execute(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }
}
