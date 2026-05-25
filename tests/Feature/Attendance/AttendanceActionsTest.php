<?php

declare(strict_types=1);

use App\Domain\Attendance\Actions\ClockInAction;
use App\Domain\Attendance\Actions\ClockOutAction;
use App\Domain\Attendance\Actions\CreateAttendanceAction;
use App\Domain\Attendance\Actions\DeleteAttendanceAction;
use App\Domain\Attendance\Actions\ProcessAbsenceAction;
use App\Domain\Attendance\Actions\SubmitAbsenceAction;
use App\Domain\Attendance\Actions\UpdateAttendanceAction;
use App\Domain\Attendance\Actions\VerifyAttendanceAction;
use App\Domain\Attendance\Enums\AbsenceReasonType;
use App\Domain\Attendance\Enums\AbsenceRequestStatus;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AbsenceRequest;
use App\Domain\Attendance\Models\Attendance;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
});

describe('CreateAttendanceAction', function () {
    it('creates attendance for a user', function () {
        $user = User::factory()->create();
        $registration = Registration::factory()->create();

        $log = app(CreateAttendanceAction::class)->execute($user, [
            'registration_id' => $registration->id,
            'date' => now()->toDateString(),
            'clock_in' => '08:00',
            'clock_out' => '16:00',
            'status' => 'present',
        ]);

        expect($log)->toBeInstanceOf(Attendance::class)
            ->and($log->user_id)->toBe($user->id)
            ->and($log->registration_id)->toBe($registration->id)
            ->and($log->status)->toBe(AttendanceStatus::PRESENT);
    });
});

describe('UpdateAttendanceAction', function () {
    it('updates an existing attendance log', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $log = Attendance::factory()->create();

        $updated = app(UpdateAttendanceAction::class)->execute($log, [
            'notes' => 'Updated notes',
            'status' => 'late',
        ]);

        expect($updated->notes)->toBe('Updated notes')
            ->and($updated->status->value)->toBe('late');
    });
});

describe('DeleteAttendanceAction', function () {
    it('deletes an attendance log', function () {
        $log = Attendance::factory()->create();

        app(DeleteAttendanceAction::class)->execute($log);

        expect(Attendance::find($log->id))->toBeNull();
    });
});

describe('ClockInAction', function () {
    it('clocks in user with active registration', function () {
        RoleModel::create(['name' => Role::SUPERVISOR->value, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $mentee = Mentee::factory()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create([
            'mentee_id' => $mentee->id,
        ]);
        $registration->setStatus('active', 'test');

        $log = app(ClockInAction::class)->execute($user, ['latitude' => -6.2, 'longitude' => 106.8]);

        expect($log)->toBeInstanceOf(Attendance::class)
            ->and($log->user_id)->toBe($user->id)
            ->and($log->clock_in)->not->toBeNull();
    });

    it('throws when no active registration', function () {
        $user = User::factory()->create();

        app(ClockInAction::class)->execute($user, []);
    })->throws(RejectedException::class);
});

describe('ClockOutAction', function () {
    it('clocks out user who has clocked in', function () {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(8)->format('H:i'),
            'clock_out' => null,
        ]);

        $log = app(ClockOutAction::class)->execute($user, []);

        expect($log->clock_out)->not->toBeNull();
    });

    it('throws when not clocked in', function () {
        $user = User::factory()->create();

        app(ClockOutAction::class)->execute($user, []);
    })->throws(RejectedException::class);
});

describe('SubmitAbsenceAction', function () {
    it('submits an absence request', function () {
        $user = User::factory()->create();
        $registration = Registration::factory()->create();

        $absence = app(SubmitAbsenceAction::class)->execute($user, [
            'registration_id' => $registration->id,
            'start_date' => now()->toDateString(),
            'reason_type' => AbsenceReasonType::SICK->value,
            'reason_description' => 'Feeling unwell',
        ]);

        expect($absence)->toBeInstanceOf(AbsenceRequest::class)
            ->and($absence->status)->toBe(AbsenceRequestStatus::PENDING);
    });
});

describe('VerifyAttendanceAction', function () {
    it('verifies an attendance log', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $log = Attendance::factory()->create();

        $verified = app(VerifyAttendanceAction::class)->execute($log);

        expect($verified->is_verified)->toBeTrue()
            ->and($verified->verified_by)->toBe($admin->id);
    });
});

describe('ProcessAbsenceAction', function () {
    it('processes an absence request', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN->value);
        $this->actingAs($admin);

        $absenceId = (string) Str::uuid();
        DB::table('absence_requests')->insert([
            'id' => $absenceId,
            'user_id' => User::factory()->create()->id,
            'registration_id' => Registration::factory()->create()->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => AbsenceReasonType::SICK->value,
            'reason_description' => 'Medical leave',
            'status' => AbsenceRequestStatus::PENDING->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $absence = AbsenceRequest::find($absenceId);

        $processed = app(ProcessAbsenceAction::class)->execute(
            $absence,
            $admin,
            AbsenceRequestStatus::APPROVED,
            'Approved after review',
        );

        expect($processed->status)->toBe(AbsenceRequestStatus::APPROVED)
            ->and($processed->processed_by)->toBe($admin->id);
    });

    it('throws when already processed', function () {
        $admin = User::factory()->create();

        $absenceId = (string) Str::uuid();
        DB::table('absence_requests')->insert([
            'id' => $absenceId,
            'user_id' => User::factory()->create()->id,
            'registration_id' => Registration::factory()->create()->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => AbsenceReasonType::SICK->value,
            'reason_description' => 'Medical leave',
            'status' => AbsenceRequestStatus::APPROVED->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $absence = AbsenceRequest::find($absenceId);

        app(ProcessAbsenceAction::class)->execute($absence, $admin, AbsenceRequestStatus::APPROVED);
    })->throws(RejectedException::class);
});
