<?php

declare(strict_types=1);

namespace App\SysAdmin\Settings\Actions;

use App\Academics\AcademicYear\Models\AcademicYear;
use Illuminate\Support\Collection;

class GetAcademicYearsAction
{
    public function execute(): Collection
    {
        return AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['name', 'start_date', 'end_date']);
    }
}
