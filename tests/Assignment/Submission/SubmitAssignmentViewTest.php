<?php

declare(strict_types=1);

use App\Modules\Assignment\Models\Assignment;
use App\Modules\Assignment\Domain\Submission\Livewire\SubmitAssignment;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('T657Z-FR-SS1: student assignments page renders without undefined relationship error', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $internship = Internship::factory()->create(['status' => 'published']);
    Registration::factory()->active()->create([
        'student_id' => $student->id,
        'internship_id' => $internship->id,
    ]);
    Assignment::factory()->published()->create([
        'internship_id' => $internship->id,
        'assignment_type' => 'report',
    ]);

    test()->actingAs($student);

    $html = Livewire::test(SubmitAssignment::class)
        ->html();

    expect($html)->toContain('report');
    expect($html)->not->toContain('Call to undefined relationship');
});
