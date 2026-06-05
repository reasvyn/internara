<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Academics\AcademicYear\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $year = (int) $now->format('Y');

        [$startYear, $endYear] = $now->month <= 6
            ? [$year - 1, $year]
            : [$year, $year + 1];

        AcademicYear::updateOrCreate(
            ['name' => "{$startYear}/{$endYear}"],
            [
                'start_date' => "{$startYear}-07-01",
                'end_date' => "{$endYear}-06-30",
                'is_active' => true,
            ],
        );
    }
}
