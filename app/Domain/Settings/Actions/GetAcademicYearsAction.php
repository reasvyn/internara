<?php

declare(strict_types=1);

namespace App\Domain\Settings\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\School\Models\AcademicYear;
use Illuminate\Support\Collection;

class GetAcademicYearsAction extends BaseAction
{
    public function execute(): Collection
    {
        return AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['name', 'start_date', 'end_date']);
    }
}
