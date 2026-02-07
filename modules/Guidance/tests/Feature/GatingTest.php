<?php

declare(strict_types=1);

namespace Modules\Guidance\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Guidance\Models\Handbook;
use Modules\Guidance\Services\Contracts\HandbookService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\User\Services\Contracts\UserService;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles and permissions via modular seeder
    $this->seed(\Modules\Permission\Database\Seeders\PermissionDatabaseSeeder::class);

    // Mock Settings to control feature state
    \Modules\Setting\Facades\Setting::shouldReceive('getValue')
        ->with('feature_guidance_enabled', true)
        ->andReturn(true);

    \Modules\Setting\Facades\Setting::shouldReceive('getValue')
        ->with('active_academic_year', \Mockery::any(), \Mockery::any())
        ->andReturn('2025/2026');

    \Modules\Setting\Facades\Setting::shouldReceive('getValue')
        ->with('attendance_late_threshold', \Mockery::any(), \Mockery::any())
        ->andReturn('08:00');

    \Modules\Setting\Facades\Setting::shouldReceive('getValue')
        ->with('journal_submission_window', \Mockery::any(), \Mockery::any())
        ->andReturn(7);
});

test(
    'it prevents journal creation if mandatory guidance is not completed [SYRS-F-101]',
    function () {
        // Factory resolution via Service Contract (Isolation Invariant)
        $userService = app(UserService::class);
        $student = $userService->factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        // Create mandatory handbook
        Handbook::factory()->create(['is_mandatory' => true, 'is_active' => true]);

        $internshipService = app(InternshipService::class);
        $internship = $internshipService->factory()->create();

        $registrationService = app(RegistrationService::class);
        $registration = $registrationService->factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
        ]);

        $journalService = app(JournalService::class);

        $this->expectException(\Modules\Exception\AppException::class);

        $journalService->create([
            'registration_id' => $registration->id,
            'student_id' => $student->id,
            'date' => now()->format('Y-m-d'),
            'work_topic' => 'Testing Gating',
            'activity_description' => 'Should fail',
        ]);
    },
);

test('it returns localized gating error messages [SYRS-NF-403]', function () {
    $handbookService = app(HandbookService::class);
    $studentId = (string) \Illuminate\Support\Str::uuid();

    // Create mandatory handbook
    Handbook::factory()->create(['is_mandatory' => true, 'is_active' => true]);

    // Test Indonesian
    app()->setLocale('id');
    try {
        app(AttendanceService::class)->checkIn($studentId);
    } catch (\Modules\Exception\AppException $e) {
        expect($e->getUserMessage())->toBe(
            'Anda wajib menyelesaikan pembekalan (membaca seluruh panduan wajib) sebelum dapat mengakses fitur ini.',
        );
    }

    // Test English
    app()->setLocale('en');
    try {
        app(AttendanceService::class)->checkIn($studentId);
    } catch (\Modules\Exception\AppException $e) {
        expect($e->getUserMessage())->toBe(
            'You must complete the briefing (read all mandatory handbooks) before you can access this feature.',
        );
    }
});

test('it allows activities after mandatory guidance is acknowledged [SYRS-F-101]', function () {
    $userService = app(UserService::class);
    $student = $userService->factory()->create();
    $student->assignRole('student');
    $this->actingAs($student);

    $handbook = Handbook::factory()->create(['is_mandatory' => true, 'is_active' => true]);

    $internship = app(InternshipService::class)->factory()->create();
    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
        ]);
    $registration->setStatus('active');

    // Acknowledge via Service
    $handbookService = app(HandbookService::class);
    $handbookService->acknowledge($student->id, $handbook->id);

    // Verify activities are now unlocked
    $log = app(AttendanceService::class)->checkIn($student->id);
    expect($log)->not->toBeNull();

    $entry = app(JournalService::class)->create([
        'registration_id' => $registration->id,
        'student_id' => $student->id,
        'date' => now()->format('Y-m-d'),
        'work_topic' => 'Testing Gating',
        'activity_description' => 'Should pass',
    ]);
    expect($entry)->not->toBeNull();
});
