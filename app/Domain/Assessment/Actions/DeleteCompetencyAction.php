<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Competency;
use App\Domain\Core\Actions\BaseAction;

final class DeleteCompetencyAction extends BaseAction
{
    public function execute(Competency $competency): void
    {
        $competency->delete();
    }
}
