<?php

declare(strict_types=1);

use App\Actions\Attendance\CreateAttendanceAction;
use App\Actions\Attendance\DeleteAttendanceAction;
use App\Actions\Attendance\UpdateAttendanceAction;
use App\Enums\Attendance\AttendanceStatus;
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

describe('CreateAttendanceAction', function () {
    beforeEach(function () {
        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);

        $this->registration = Registration::factory()->create();
        $this->student = User::factory()->create();
    });

    it('creates attendance from input', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
                'clock_in' => '08:00:00',
                'clock_out' => '17:00:00',
                'status' => AttendanceStatus::PRESENT->value,
            ],
        );

        expect($log)->toBeInstanceOf(Attendance::class);
        expect($log->id)->toBeUuid();
        expect($log->user_id)->toBe($this->student->id);
        expect($log->registration_id)->toBe($this->registration->id);
        expect($log->date->format('Y-m-d'))->toBe('2025-06-15');
    });

    it('defaults status to present', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
            ],
        );

        expect($log->status)->toBe(AttendanceStatus::PRESENT);
    });

    it('defaults is_verified to false', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
            ],
        );

        expect($log->is_verified)->toBeFalse();
    });

    it('accepts explicit status', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
                'status' => AttendanceStatus::SICK->value,
            ],
        );

        expect($log->status)->toBe(AttendanceStatus::SICK);
    });

    it('sets verified_by when is_verified is true', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
                'is_verified' => true,
            ],
        );

        expect($log->is_verified)->toBeTrue();
        expect($log->verified_by)->toBe($this->actor->id);
        expect($log->verified_at)->not->toBeNull();
    });

    it('persists to database', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
            ],
        );

        expect(Attendance::find($log->id))->not->toBeNull();
    });

    it('creates activity log', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
            ],
        );

        $activity = Activity::where('event', 'attendance_created')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->actor->id);
        expect($activity->subject_id)->toBe($log->id);
    });
});

describe('UpdateAttendanceAction', function () {
    beforeEach(function () {
        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);
    });

    it('updates attendance fields', function () {
        $log = Attendance::factory()->create();

        $result = app(UpdateAttendanceAction::class)->execute($log, [
            'clock_out' => '18:00:00',
            'notes' => 'Worked overtime.',
        ]);

        expect($result->fresh()->clock_out->format('H:i'))->toBe('18:00');
        expect($result->fresh()->notes)->toBe('Worked overtime.');
    });

    it('does not change fields not in update data', function () {
        $log = Attendance::factory()->create([
            'clock_in' => '08:00:00',
            'notes' => 'Original note.',
        ]);

        app(UpdateAttendanceAction::class)->execute($log, [
            'notes' => 'Updated note.',
        ]);

        expect($log->fresh()->notes)->toBe('Updated note.');
        expect($log->fresh()->clock_in->format('H:i'))->toBe('08:00');
    });

    it('sets verified_by on verification', function () {
        $log = Attendance::factory()->create(['is_verified' => false]);

        app(UpdateAttendanceAction::class)->execute($log, [
            'is_verified' => true,
        ]);

        expect($log->fresh()->is_verified)->toBeTrue();
        expect($log->fresh()->verified_by)->toBe($this->actor->id);
    });

    it('returns the same instance', function () {
        $log = Attendance::factory()->create();

        $result = app(UpdateAttendanceAction::class)->execute($log, [
            'notes' => 'Updated',
        ]);

        expect($result->id)->toBe($log->id);
    });

    it('creates activity log on update', function () {
        $log = Attendance::factory()->create();

        app(UpdateAttendanceAction::class)->execute($log, [
            'notes' => 'Logged update',
        ]);

        $activity = Activity::where('event', 'attendance_updated')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->actor->id);
        expect($activity->subject_id)->toBe($log->id);
    });

    it('succeeds with no changed attributes', function () {
        $log = Attendance::factory()->create();

        $result = app(UpdateAttendanceAction::class)->execute($log, []);

        expect($result->id)->toBe($log->id);
    });
});

describe('DeleteAttendanceAction', function () {
    beforeEach(function () {
        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);
    });

    it('deletes attendance from database', function () {
        $log = Attendance::factory()->create();
        $logId = $log->id;

        app(DeleteAttendanceAction::class)->execute($log);

        expect(Attendance::find($logId))->toBeNull();
    });

    it('creates activity log on delete', function () {
        $log = Attendance::factory()->create();
        $logId = $log->id;

        app(DeleteAttendanceAction::class)->execute($log);

        $activity = Activity::where('event', 'attendance_deleted')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->actor->id);
        expect($activity->subject_id)->toBe($logId);
    });

    it('decrements attendance count', function () {
        Attendance::factory()->count(3)->create();

        $log = Attendance::first();

        app(DeleteAttendanceAction::class)->execute($log);

        expect(Attendance::count())->toBe(2);
    });
});
