<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\School\Models\AcademicYear;

/**
 * Creates a new academic year.
 */
class CreateAcademicYearAction
{
    public function execute(array $data): AcademicYear
    {
        return AcademicYear::create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => $data['is_active'] ?? false,
        ]);
    }
}
