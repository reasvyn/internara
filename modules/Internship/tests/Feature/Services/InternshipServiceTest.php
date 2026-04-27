<?php

declare(strict_types=1);

use Modules\Assignment\Database\Seeders\AssignmentSeeder;
use Modules\Internship\Models\Internship;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Permission\Models\Role;
use Modules\School\Services\Contracts\SchoolService;
use Modules\User\Models\User;

beforeEach(function () {
    $this->seed(AssignmentSeeder::class);

    Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);

    $this->internshipService = app(InternshipService::class);
});

test('it automatically creates default assignments when an internship is created', function () {
    $school = app(SchoolService::class)->factory()->create();

    $data = [
        'school_id' => $school->id,
        'title' => 'Program Magang 2026',
        'academic_year' => '2026/2027',
        'semester' => 'Ganjil',
        'date_start' => now()->toDateString(),
        'date_finish' => now()->addMonths(6)->toDateString(),
    ];

    $internship = $this->internshipService->create($data);

    expect($internship)->toBeInstanceOf(Internship::class);

    $this->assertDatabaseHas('assignments', [
        'internship_id' => $internship->id,
        'title' => 'Laporan Kegiatan PKL',
    ]);

    $this->assertDatabaseHas('assignments', [
        'internship_id' => $internship->id,
        'title' => 'Presentasi Kegiatan PKL',
    ]);
});
