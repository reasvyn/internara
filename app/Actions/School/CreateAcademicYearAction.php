<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Core\LogAuditAction;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAcademicYearAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
    ) {}

    public function execute(array $data): AcademicYear
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:50', 'unique:academic_years,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        return DB::transaction(function () use ($validated) {
            $year = AcademicYear::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => $validated['is_active'] ?? false,
            ]);

            $this->logAudit->execute(
                action: 'academic_year_created',
                subjectType: AcademicYear::class,
                subjectId: $year->id,
                payload: $validated,
            );

            return $year;
        });
    }
}
