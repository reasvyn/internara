<?php

declare(strict_types=1);

use App\Actions\Attendance\ClockInAction;
use App\Actions\Attendance\ClockOutAction;
use App\Actions\Attendance\CreateAttendanceAction;
use App\Actions\Attendance\DeleteAttendanceAction;
use App\Actions\Attendance\UpdateAttendanceAction;
use App\Actions\Attendance\VerifyAttendanceAction;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\QueryException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);

    $this->actor = User::factory()->create();
    $this->actingAs($this->actor);

    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->registration = Registration::factory()->create([
        'student_id' => $this->student->id,
    ]);
    $this->registration->setStatus('active', 'Test setup');
});

describe('clock in', function () {

    it('creates attendance on clock in', function () {
        $log = app(ClockInAction::class)->execute($this->student, []);

        expect($log->user_id)->toBe($this->student->id);
        expect($log->registration_id)->toBe($this->registration->id);
        expect($log->clock_in)->not->toBeNull();
        expect($log->status->value)->toBe('present');
        expect($log->date->format('Y-m-d'))->toBe(now()->toDateString());
        expect($log->clock_out)->toBeNull();
        assertDatabaseHas('attendances', ['id' => $log->id]);
    });

    it('records clock in IP and coordinates when provided', function () {
        $log = app(ClockInAction::class)->execute(
            $this->student,
            ['latitude' => -6.2088, 'longitude' => 106.8456],
            '192.168.1.1',
        );

        expect($log->clock_in_ip)->toBe('192.168.1.1');
        expect(round((float) $log->clock_in_latitude, 4))->toBe(-6.2088);
        expect(round((float) $log->clock_in_longitude, 4))->toBe(106.8456);
    });

    it('throws if already clocked in today', function () {
        app(ClockInAction::class)->execute($this->student, []);
        app(ClockInAction::class)->execute($this->student, []);
    })->throws(RuntimeException::class, 'Already clocked in for today.');

    it('throws if no active registration', function () {
        $newStudent = User::factory()->create();
        app(ClockInAction::class)->execute($newStudent, []);
    })->throws(RuntimeException::class, 'No active internship registration found.');

    it('logs audit on clock in', function () {
        $log = app(ClockInAction::class)->execute($this->student, []);

        $activity = Activity::where('event', 'clock_in')->first();
        expect($activity)->not->toBeNull();
        expect($activity->subject_id)->toBe($log->id);
    });

});

describe('clock out', function () {

    it('updates attendance on clock out', function () {
        $log = app(ClockInAction::class)->execute($this->student, []);

        $result = app(ClockOutAction::class)->execute(
            $this->student,
            ['latitude' => -6.2, 'longitude' => 106.8],
            '10.0.0.1',
        );

        expect($result->id)->toBe($log->id);
        expect($result->clock_out)->not->toBeNull();
        expect($result->clock_out_ip)->toBe('10.0.0.1');
        expect($result->fresh()->clock_out)->not->toBeNull();
    });

    it('throws if not clocked in', function () {
        app(ClockOutAction::class)->execute($this->student, []);
    })->throws(RuntimeException::class, 'You must clock in first.');

    it('throws if already clocked out', function () {
        app(ClockInAction::class)->execute($this->student, []);
        app(ClockOutAction::class)->execute($this->student, []);
        app(ClockOutAction::class)->execute($this->student, []);
    })->throws(RuntimeException::class, 'Already clocked out for today.');

    it('logs audit on clock out', function () {
        app(ClockInAction::class)->execute($this->student, []);
        $log = app(ClockOutAction::class)->execute($this->student, []);

        $activity = Activity::where('event', 'clock_out')->first();
        expect($activity)->not->toBeNull();
        expect($activity->subject_id)->toBe($log->id);
    });

});

describe('create entry (admin)', function () {

    it('creates attendance entry with all fields', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
                'clock_in' => '08:00:00',
                'clock_out' => '17:00:00',
                'status' => AttendanceStatus::PRESENT->value,
                'notes' => 'Full day present.',
            ],
        );

        expect($log->user_id)->toBe($this->student->id);
        expect($log->registration_id)->toBe($this->registration->id);
        expect($log->date->format('Y-m-d'))->toBe('2025-06-15');
        expect($log->clock_in->format('H:i'))->toBe('08:00');
        expect($log->clock_out->format('H:i'))->toBe('17:00');
        expect($log->status)->toBe(AttendanceStatus::PRESENT);
        expect($log->notes)->toBe('Full day present.');
        expect($log->id)->toBeUuid();
    });

    it('defaults status to present and is_verified to false', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
            ],
        );

        expect($log->status)->toBe(AttendanceStatus::PRESENT);
        expect($log->is_verified)->toBeFalse();
        expect($log->verified_by)->toBeNull();
        expect($log->verified_at)->toBeNull();
    });

    it('accepts different status values', function () {
        $statuses = [
            AttendanceStatus::LATE,
            AttendanceStatus::SICK,
            AttendanceStatus::ABSENT,
            AttendanceStatus::PERMISSION,
            AttendanceStatus::EARLY_OUT,
        ];

        $day = 10;
        foreach ($statuses as $status) {
            $log = app(CreateAttendanceAction::class)->execute(
                user: $this->student,
                data: [
                    'registration_id' => $this->registration->id,
                    'date' => '2025-06-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                    'status' => $status->value,
                ],
            );

            expect($log->status)->toBe($status);
            $day++;
        }
    });

    it('sets verified_by on creation when is_verified is true', function () {
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

    it('persists entry to database', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
            ],
        );

        assertDatabaseHas('attendances', ['id' => $log->id]);
    });

    it('logs audit on creation', function () {
        $log = app(CreateAttendanceAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-06-15',
            ],
        );

        $activity = Activity::where('event', 'attendance_created')->first();
        expect($activity)->not->toBeNull();
        expect($activity->subject_id)->toBe($log->id);
    });

});

describe('update entry', function () {

    it('updates clock_out on existing entry', function () {
        $log = Attendance::factory()->create([
            'user_id' => $this->student->id,
            'registration_id' => $this->registration->id,
            'clock_in' => '08:00:00',
            'clock_out' => null,
        ]);

        app(UpdateAttendanceAction::class)->execute($log, [
            'clock_out' => '17:30:00',
        ]);

        expect($log->fresh()->clock_out->format('H:i'))->toBe('17:30');
    });

    it('updates notes on attendance entry', function () {
        $log = Attendance::factory()->create();

        app(UpdateAttendanceAction::class)->execute($log, [
            'notes' => 'Updated note.',
        ]);

        expect($log->fresh()->notes)->toBe('Updated note.');
    });

    it('verifies attendance on update', function () {
        $log = Attendance::factory()->create([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        app(UpdateAttendanceAction::class)->execute($log, [
            'is_verified' => true,
        ]);

        expect($log->fresh()->is_verified)->toBeTrue();
        expect($log->fresh()->verified_by)->toBe($this->actor->id);
        expect($log->fresh()->verified_at)->not->toBeNull();
    });

    it('does not modify fields not in update data', function () {
        $log = Attendance::factory()->create([
            'clock_in' => '07:30:00',
            'notes' => 'Original.',
        ]);

        app(UpdateAttendanceAction::class)->execute($log, [
            'notes' => 'Changed.',
        ]);

        $fresh = $log->fresh();
        expect($fresh->notes)->toBe('Changed.');
        expect($fresh->clock_in->format('H:i'))->toBe('07:30');
    });

    it('logs audit on update', function () {
        $log = Attendance::factory()->create();

        app(UpdateAttendanceAction::class)->execute($log, [
            'notes' => 'Audited update.',
        ]);

        $activity = Activity::where('event', 'attendance_updated')->first();
        expect($activity)->not->toBeNull();
        expect($activity->subject_id)->toBe($log->id);
    });

});

describe('verify entry', function () {

    it('marks attendance as verified', function () {
        $log = Attendance::factory()->create([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $result = app(VerifyAttendanceAction::class)->execute($log);

        expect($result->is_verified)->toBeTrue();
        expect($result->verified_by)->toBe($this->actor->id);
        expect($result->verified_at)->not->toBeNull();
        expect($result->fresh()->is_verified)->toBeTrue();
    });

    it('is idempotent when already verified', function () {
        $log = Attendance::factory()->create([
            'is_verified' => true,
            'verified_by' => User::factory()->create()->id,
            'verified_at' => now()->subHour(),
        ]);

        app(VerifyAttendanceAction::class)->execute($log);

        expect($log->fresh()->is_verified)->toBeTrue();
    });

    it('logs audit on verify', function () {
        $log = Attendance::factory()->create();

        app(VerifyAttendanceAction::class)->execute($log);

        $activity = Activity::where('event', 'attendance_verified')->first();
        expect($activity)->not->toBeNull();
        expect($activity->subject_id)->toBe($log->id);
    });

});

describe('delete entry', function () {

    it('removes attendance from database', function () {
        $log = Attendance::factory()->create();
        $logId = $log->id;

        app(DeleteAttendanceAction::class)->execute($log);

        assertDatabaseMissing('attendances', ['id' => $logId]);
        expect(Attendance::find($logId))->toBeNull();
    });

    it('logs audit on delete', function () {
        $log = Attendance::factory()->create();
        $logId = $log->id;

        app(DeleteAttendanceAction::class)->execute($log);

        $activity = Activity::where('event', 'attendance_deleted')->first();
        expect($activity)->not->toBeNull();
        expect($activity->subject_id)->toBe($logId);
    });

    it('decrements attendance count', function () {
        Attendance::factory()->count(3)->create();

        $log = Attendance::first();
        app(DeleteAttendanceAction::class)->execute($log);

        expect(Attendance::count())->toBe(2);
    });

});

describe('unique constraint', function () {

    it('prevents duplicate attendance on same date for same user', function () {
        Attendance::factory()->create([
            'user_id' => $this->student->id,
            'registration_id' => $this->registration->id,
            'date' => '2025-06-15',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->student->id,
            'registration_id' => $this->registration->id,
            'date' => '2025-06-15',
        ]);
    })->throws(QueryException::class);

});
