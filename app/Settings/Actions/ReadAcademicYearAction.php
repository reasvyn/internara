<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Actions\BaseReadAction;
use Illuminate\Support\Collection;

class ReadAcademicYearAction extends BaseReadAction
{
    public function execute(): Collection
    {
        return AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['name', 'start_date', 'end_date']);
    }
}
