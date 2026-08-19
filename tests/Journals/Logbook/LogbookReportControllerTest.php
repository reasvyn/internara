<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Models\Logbook;
use App\Program\Internship\Models\Internship;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('1KSWL-FR-LB8: logbook report endpoint returns a PDF of verified entries', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $student = User::factory()->create();

    $internship = Internship::factory()->create(['status' => 'published']);
    $registration = Registration::factory()->active()->create([
        'student_id' => $student->id,
        'internship_id' => $internship->id,
    ]);

    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'user_id' => $student->id,
    ]);

    Logbook::factory()->create([
        'user_id' => $student->id,
        'registration_id' => $registration->id,
        'date' => '2026-08-18',
        'status' => 'verified',
    ]);

    $this->actingAs($admin)
        ->get(route('sysadmin.logbook.report', $registration->id))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
