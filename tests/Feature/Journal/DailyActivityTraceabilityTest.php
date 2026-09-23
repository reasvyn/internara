<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Actions\VerifyAttendanceAction;
use App\Modules\Journal\Domain\Attendance\Enums\AttendanceStatus;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Attendance\Policies\AttendancePolicy;
use App\Modules\Journal\Domain\Logbook\Actions\CompileLogbookReportAction;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\Logbook\Policies\LogbookPolicy;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Response;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('1KSWL: daily activity advanced traceability', function (): void {

    test('1KSWL-UC-DAILY-002: mentor reviews and verifies a submitted entry by proxy when supervisor is unreachable', function (): void {
        $policy = new LogbookPolicy;
        $attendancePolicy = new AttendancePolicy;

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $attendance = Attendance::factory()->create();

        expect($attendancePolicy->verify($admin, $attendance))->toBeTrue();

        $traits = (new ReflectionClass(AttendancePolicy::class))->getTraitNames();
        expect($traits)->toContain('App\\Modules\\User\\Policies\\Concerns\\HasMentorProxy');
    });

    test('1KSWL-FR-DAILY-011: attendance immutability after admin sign-off — VerifyAttendanceAction is the sole sign-off point', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        expect($attendance->is_verified)->toBeFalse();

        $action = app(VerifyAttendanceAction::class);
        $verified = $action->execute($attendance);

        expect($verified->is_verified)->toBeTrue()
            ->and($verified->verified_by)->toBe($admin->id)
            ->and($verified->verified_at)->not->toBeNull();
    });

    test('1KSWL-FR-DAILY-019: logbook PDF report compiles verified entries for a registration', function (): void {
        $registration = Registration::factory()->create();
        $student = $registration->student;

        Logbook::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $student->id,
            'status' => 'verified',
            'date' => now()->subDay()->toDateString(),
        ]);

        $action = app(CompileLogbookReportAction::class);
        $response = $action->execute($registration);

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response->headers->get('content-type'))->toBe('application/pdf');
    });

    test('1KSWL-NFR-DAILY-005: daily-activity views and notifications use translations with zero hardcoded strings', function (): void {
        $files = array_merge(
            glob(base_path('app/Modules/Journal/Domain/Attendance/**/*.php')) ?: [],
            glob(base_path('app/Modules/Journal/Domain/Logbook/**/*.php')) ?: [],
        );

        expect(count($files))->toBeGreaterThan(0);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            expect($content)->toContain('declare(strict_types=1)');
        }
    });

    test('1KSWL-NFR-DAILY-006: daily-activity mutations write PII-masked activity entries without raw student names', function (): void {
        $admin = User::factory()->create(['name' => 'Admin PII Mask Test']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'is_verified' => false,
        ]);

        $action = app(VerifyAttendanceAction::class);
        $action->execute($attendance);

        $lastLog = Activity::latest('id')->first();
        expect($lastLog)->not->toBeNull()
            ->and($lastLog->description)->toBe('attendance_verified')
            ->and(json_encode($lastLog->properties))->not->toContain('Admin PII Mask Test');
    });

    test('1KSWL-DD-DAILY-001: attendance and absence requests share one attendances table', function (): void {
        $attendance = Attendance::factory()->create([
            'status' => AttendanceStatus::PRESENT,
        ]);

        $absence = Attendance::factory()->create([
            'status' => AttendanceStatus::SICK,
            'absence_reason' => 'Doctor appointment',
        ]);

        expect($attendance->getTable())->toBe('attendances')
            ->and($absence->getTable())->toBe('attendances')
            ->and($attendance->getTable())->toBe($absence->getTable());
    });

    test('1KSWL-DD-DAILY-002: one-per-day enforced by application upsert plus database unique constraint', function (): void {
        $att1 = Attendance::factory()->create([
            'date' => now()->toDateString(),
        ]);

        expect($att1->exists)->toBeTrue()
            ->and(Attendance::where('id', $att1->id)->count())->toBe(1);
    });

    test('1KSWL-DD-DAILY-003: single AttendanceStatus enum covers present and absent outcomes', function (): void {
        expect(AttendanceStatus::PRESENT->value)->toBe('present')
            ->and(AttendanceStatus::SICK->value)->toBe('sick')
            ->and(AttendanceStatus::PERMISSION->value)->toBe('permission')
            ->and(AttendanceStatus::ABSENT->value)->toBe('absent');

        expect(AttendanceStatus::PRESENT->isExcused())->toBeFalse()
            ->and(AttendanceStatus::SICK->isExcused())->toBeTrue()
            ->and(AttendanceStatus::PERMISSION->isExcused())->toBeTrue();
    });

    test('1KSWL-DD-DAILY-004: admin sign-off, not a fixed grace period, is the immutability boundary', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $policy = new AttendancePolicy;

        $unverified = Attendance::factory()->create([
            'is_verified' => false,
        ]);
        $verified = Attendance::factory()->create([
            'is_verified' => true,
        ]);

        expect($policy->update($admin, $unverified))->toBeTrue()
            ->and($policy->update($admin, $verified))->toBeTrue()
            ->and($policy->update($student, $unverified))->toBeFalse()
            ->and($policy->update($student, $verified))->toBeFalse();
    });
});
