<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Events\InternshipCreated;
use App\Domain\Internship\Models\Internship;
use App\Domain\School\Models\AcademicYear;

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
