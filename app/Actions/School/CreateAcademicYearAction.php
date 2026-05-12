<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\Validator;

/**
 * Creates a new academic year.
 */
class CreateAcademicYearAction
{
    public function execute(array $data): AcademicYear
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        return AcademicYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $validated['is_active'] ?? false,
        ]);
    }
}
