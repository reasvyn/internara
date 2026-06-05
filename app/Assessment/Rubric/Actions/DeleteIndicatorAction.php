<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Actions;

use App\Assessment\Rubric\Models\Indicator;
use App\Core\Actions\BaseAction;

final class DeleteIndicatorAction extends BaseAction
{
    public function execute(Indicator $indicator): void
    {
        $indicator->delete();
    }
}
