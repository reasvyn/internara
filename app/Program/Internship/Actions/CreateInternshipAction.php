<?php

declare(strict_types=1);

namespace App\Program\Internship\Actions;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Actions\BaseAction;
use App\Program\Internship\Events\InternshipCreated;
use App\Program\Internship\Models\Internship;

final class CreateInternshipAction extends BaseAction
{
    public function execute(array $data): Internship
    {
        if (empty($data['academic_year_id'])) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            if ($activeYear !== null) {
                $data['academic_year_id'] = $activeYear->id;
            } else {
                unset($data['academic_year_id']);
            }
        }

        return $this->transaction(function () use ($data) {
            $internship = Internship::create($data);

            $this->log('internship_created', $internship, ['name' => $internship->name, 'academic_year_id' => $internship->academic_year_id]);

            event(new InternshipCreated($internship, auth()->user()));

            return $internship;
        });
    }
}
