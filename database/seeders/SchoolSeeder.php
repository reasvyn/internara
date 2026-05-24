<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        if (School::count() > 0) {
            return;
        }

        $school = School::create([
            'name' => 'My Institution',
            'institutional_code' => 'INST-'.date('Y'),
            'address' => 'Jl. Example No. 123',
            'email' => 'school@example.com',
            'phone' => '02112345678',
        ]);

        Department::create([
            'name' => 'Software Engineering',
            'description' => 'Rekayasa Perangkat Lunak',
            'school_id' => $school->id,
        ]);

        Department::create([
            'name' => 'Network Engineering',
            'description' => 'Teknik Komputer dan Jaringan',
            'school_id' => $school->id,
        ]);

        $currentYear = (int) date('Y');

        AcademicYear::create([
            'name' => ($currentYear - 1).'/'.$currentYear,
            'start_date' => ($currentYear - 1).'-07-01',
            'end_date' => $currentYear.'-06-30',
            'is_active' => true,
        ]);

        AcademicYear::create([
            'name' => $currentYear.'/'.($currentYear + 1),
            'start_date' => $currentYear.'-07-01',
            'end_date' => ($currentYear + 1).'-06-30',
            'is_active' => false,
        ]);
    }
}
