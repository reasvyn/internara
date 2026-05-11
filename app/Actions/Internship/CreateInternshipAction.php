<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Events\Internship\InternshipCreated;
use App\Models\AcademicYear;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;

class CreateInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): Internship
    {
        if (empty($data['academic_year_id'])) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            if ($activeYear !== null) {
                $data['academic_year_id'] = $activeYear->id;
            }
        }

        return DB::transaction(function () use ($data) {
            $internship = Internship::create($data);

            $this->logAudit->execute(
                action: 'internship_created',
                subjectType: Internship::class,
                subjectId: $internship->id,
                payload: ['name' => $internship->name],
                module: 'Internship',
            );

            event(new InternshipCreated($internship, auth()->user()));

            return $internship;
        });
    }
}
