<?php

declare(strict_types=1);

namespace App\Actions\Rubric;

use App\Models\Indicator;

class DeleteIndicatorAction
{
    public function execute(Indicator $indicator): void
    {
        $indicator->delete();
    }
}
