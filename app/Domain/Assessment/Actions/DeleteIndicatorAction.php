<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Indicator;
use App\Domain\Core\Actions\BaseAction;

final class DeleteIndicatorAction extends BaseAction
{
    public function execute(Indicator $indicator): void
    {
        $indicator->delete();
    }
}
