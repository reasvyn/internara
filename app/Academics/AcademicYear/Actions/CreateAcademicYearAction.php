<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Actions;

use App\Academics\AcademicYear\Events\AcademicYearCreated;
use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Actions\BaseCommandAction;
use Illuminate\Support\Facades\Validator;

final class CreateAcademicYearAction extends BaseCommandAction
{
    public function execute(array $data): AcademicYear
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:50', 'unique:academic_years,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        return $this->transaction(function () use ($validated) {
            $year = AcademicYear::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => $validated['is_active'] ?? false,
            ]);

            $this->dispatchEvent(new AcademicYearCreated($year));

            $this->log('academic_year_created', $year, $validated);

            return $year;
        });
    }
}
