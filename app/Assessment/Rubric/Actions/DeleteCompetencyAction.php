<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Competency;
use App\Core\Actions\BaseAction;

final class DeleteCompetencyAction extends BaseAction
{
    public function execute(Competency $competency): void
    {
        $competency->delete();
    }
}
