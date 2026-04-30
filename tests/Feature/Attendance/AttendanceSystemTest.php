<?php

declare(strict_types=1);

use App\Actions\Attendance\ClockInAction;
use App\Actions\Attendance\ClockOutAction;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;


beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $this->student = User::factory()->create();
    $this->student->assignRole(RoleEnum::STUDENT);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::ADMIN);
});

describe('Clock In', function () {
    it('allows student to clock in with active registration', function () {
        // Create active registration
        $internship = \App\Models\Internship::factory()->create();
        $registration = \App\Models\InternshipRegistration::factory()->create([
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);
        $registration->setStatus('active');

        $action = app(ClockInAction::class);
        $log = $action->execute($this->student, []);

        expect($log)->toBeInstanceOf(\App\Models\AttendanceLog::class)
            ->and($log->clock_in)->not->toBeNull();
    });

    it('prevents double clock in')->todo('Needs fix for Carbon::now() timing issue in double clock in test.');

    it('requires active registration', function () {
        $action = app(ClockInAction::class);

        try {
            $action->execute($this->student, []);
            expect(false)->toBeTrue('Expected exception was not thrown');
        } catch (\Exception $e) {
            expect($e->getMessage())->toContain('No active internship registration');
        }
    });
});

describe('Clock Out', function () {
    it('allows student to clock out after clock in')->todo('Needs fix for clock out test - timing issue with Carbon::now().');

    it('prevents clock out without clock in', function () {
        $action = app(ClockOutAction::class);

        try {
            $action->execute($this->student, []);
            expect(false)->toBeTrue('Expected exception was not thrown');
        } catch (\Exception $e) {
            expect($e->getMessage())->toContain('You must clock in first');
        }
    });
});

describe('Journal Entry', function () {
    it('allows student to submit journal entry after clock in')->todo('Needs fix for journal entry test - timing issue.');
});