<?php

declare(strict_types=1);

use App\Actions\Attendance\ClockInAction;
use App\Actions\Attendance\ClockOutAction;
use App\Actions\Attendance\VerifyAttendanceAction;
use App\Models\Attendance;
use App\Models\Registration;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

describe('ClockInAction', function () {
    beforeEach(function () {
        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $registration = Registration::factory()->create([
            'student_id' => $this->student->id,
        ]);
        $registration->setStatus('active', 'Test setup');
    });

    it('creates attendance log on clock in', function () {
        $log = app(ClockInAction::class)->execute($this->student, []);

        expect($log)->toBeInstanceOf(Attendance::class);
        expect($log->user_id)->toBe($this->student->id);
        expect($log->clock_in)->not->toBeNull();
        expect($log->status->value)->toBe('present');
        expect($log->date->format('Y-m-d'))->toBe(now()->toDateString());
    });

    it('throws if already clocked in today', function () {
        Attendance::factory()->create([
            'user_id' => $this->student->id,
            'registration_id' => Registration::where('student_id', $this->student->id)->first()->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(8),
        ]);

        app(ClockInAction::class)->execute($this->student, []);
    })->throws(RuntimeException::class, 'Already clocked in for today.');

    it('throws if no active registration', function () {
        $studentWithNoReg = User::factory()->create();

        app(ClockInAction::class)->execute($studentWithNoReg, []);
    })->throws(RuntimeException::class, 'No active internship registration found.');
});

describe('ClockOutAction', function () {
    beforeEach(function () {
        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $registration = Registration::factory()->create([
            'student_id' => $this->student->id,
        ]);
        $registration->setStatus('active', 'Test setup');
    });

    it('updates attendance log on clock out', function () {
        $log = app(ClockInAction::class)->execute($this->student, []);

        $result = app(ClockOutAction::class)->execute($this->student, []);

        expect($result->id)->toBe($log->id);
        expect($result->clock_out)->not->toBeNull();
        expect($result->fresh()->clock_out)->not->toBeNull();
    });

    it('throws if not clocked in yet', function () {
        app(ClockOutAction::class)->execute($this->student, []);
    })->throws(RuntimeException::class, 'You must clock in first.');

    it('throws if already clocked out', function () {
        app(ClockInAction::class)->execute($this->student, []);
        app(ClockOutAction::class)->execute($this->student, []);

        app(ClockOutAction::class)->execute($this->student, []);
    })->throws(RuntimeException::class, 'Already clocked out for today.');
});

describe('VerifyAttendanceAction', function () {
    beforeEach(function () {
        $this->mentor = User::factory()->create();
        $this->actingAs($this->mentor);
    });

    it('verifies attendance log', function () {
        $log = Attendance::factory()->create([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $result = app(VerifyAttendanceAction::class)->execute($log);

        expect($result->is_verified)->toBeTrue();
        expect($result->verified_by)->toBe($this->mentor->id);
        expect($result->verified_at)->not->toBeNull();
        expect($result->fresh()->is_verified)->toBeTrue();
    });

    it('creates activity log on verify', function () {
        $log = Attendance::factory()->create();

        app(VerifyAttendanceAction::class)->execute($log);

        $activity = Activity::where('event', 'attendance_verified')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->mentor->id);
        expect($activity->subject_id)->toBe($log->id);
    });

    it('returns the same log instance', function () {
        $log = Attendance::factory()->create();

        $result = app(VerifyAttendanceAction::class)->execute($log);

        expect($result->id)->toBe($log->id);
    });
});
