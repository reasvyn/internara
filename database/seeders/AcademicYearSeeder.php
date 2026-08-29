<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Academics\Domain\AcademicYear\Support\AcademicYearPeriod;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::updateOrCreate(
            ['name' => AcademicYearPeriod::nameFor(now())],
            [
                'start_date' => AcademicYearPeriod::startDateFor(now()),
                'end_date' => AcademicYearPeriod::endDateFor(now()),
                'is_active' => true,
            ],
        );
    }
}
