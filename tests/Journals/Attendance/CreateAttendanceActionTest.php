<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Attendance\Actions\CreateAttendanceAction;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('1KSWL-FR-AT6: teacher-created attendance is attributed to the student, not the acting user', function () {
    $teacher = User::factory()->create();
    $student = User::factory()->create();

    $internship = Internship::factory()->create(['status' => 'published']);
    $registration = Registration::factory()->active()->create([
        'student_id' => $student->id,
        'internship_id' => $internship->id,
    ]);

    $action = app(CreateAttendanceAction::class);
    $action->execute($teacher, [
        'registration_id' => $registration->id,
        'user_id' => $student->id,
        'date' => '2026-08-19',
        'status' => 'present',
    ]);

    $log = Attendance::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($student->id)
        ->and($log->registration_id)->toBe($registration->id);
});
