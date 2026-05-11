<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Models\Competency;

class DeleteCompetencyAction
{
    public function execute(Competency $competency): void
    {
        $competency->delete();
    }
}
