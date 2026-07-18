<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Actions\BaseReadAction;
use Illuminate\Support\Collection;

final class ReadAcademicYearAction extends BaseReadAction
{
    public function execute(): Collection
    {
        return AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['name', 'start_date', 'end_date']);
    }

    public function findByName(string $name): ?AcademicYear
    {
        return AcademicYear::where('name', $name)->first();
    }
}
