<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Partners\Company\Models\Company;
use App\Program\Internship\Models\Internship;
use App\Program\InternshipGroup\Actions\CreateInternshipGroupAction;
use App\Program\InternshipGroup\Models\InternshipGroup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates internship group', function () {
    $company = Company::factory()->create();
    $year = AcademicYear::factory()->create();
    $internship = Internship::factory()->create([
        'academic_year_id' => $year->id,
    ]);

    $action = app(CreateInternshipGroupAction::class);
    $group = $action->execute([
        'internship_id' => $internship->id,
        'name' => 'Group A',
    ]);

    expect($group)->toBeInstanceOf(InternshipGroup::class);
    $this->assertDatabaseHas('internship_groups', ['name' => 'Group A']);
});
