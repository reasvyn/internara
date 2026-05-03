<?php

declare(strict_types=1);

use App\Domain\Attendance\Actions\ClockInAction;
use App\Domain\Attendance\Actions\ClockOutAction;
use App\Domain\Attendance\Models\AttendanceLog;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\Registration;
use App\Domain\Logbook\Actions\SubmitLogbookEntryAction;
use App\Domain\Logbook\Models\LogbookEntry;
use App\Domain\User\Models\User;
use App\Enums\Auth\Role as RoleEnum;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

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
        $internship = Internship::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);
        $registration->setStatus('active');

        Carbon::setTestNow(Carbon::create(2026, 4, 30, 8, 0, 0));

        $action = app(ClockInAction::class);
        $log = $action->execute($this->student, [], '127.0.0.1');

        expect($log)->toBeInstanceOf(AttendanceLog::class)->and($log->clock_in)->not->toBeNull();

        Carbon::setTestNow();
    });

    it('prevents double clock in', function () {
        $internship = Internship::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);
        $registration->setStatus('active');

        Carbon::setTestNow(Carbon::create(2026, 4, 30, 8, 0, 0));

        $action = app(ClockInAction::class);
        $action->execute($this->student, [], '127.0.0.1');

        // The duplicate check should throw a RuntimeException
        expect(fn () => $action->execute($this->student, [], '127.0.0.1'))->toThrow(
            Exception::class,
        );

        Carbon::setTestNow();
    });

    it('requires active registration', function () {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 8, 0, 0));

        $action = app(ClockInAction::class);

        expect(fn () => $action->execute($this->student, [], '127.0.0.1'))->toThrow(
            RuntimeException::class,
            'No active internship registration found.',
        );

        Carbon::setTestNow();
    });
});

describe('Clock Out', function () {
    it('allows student to clock out after clock in', function () {
        $internship = Internship::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);
        $registration->setStatus('active');

        // Clock in
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 8, 0, 0));

        $clockInAction = app(ClockInAction::class);
        $clockInLog = $clockInAction->execute($this->student, [], '127.0.0.1');

        expect($clockInLog->clock_in)->not->toBeNull();

        // Clock out - same date, different time
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 17, 0, 0));

        $clockOutAction = app(ClockOutAction::class);
        $clockOutLog = $clockOutAction->execute($this->student, [], '127.0.0.1');

        expect($clockOutLog->clock_out)
            ->not->toBeNull()
            ->and($clockOutLog->id)
            ->toBe($clockInLog->id);

        Carbon::setTestNow();
    });

    it('prevents clock out without clock in', function () {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 17, 0, 0));

        $action = app(ClockOutAction::class);

        expect(fn () => $action->execute($this->student, [], '127.0.0.1'))->toThrow(
            RuntimeException::class,
            'You must clock in first.',
        );

        Carbon::setTestNow();
    });
});

describe('Journal Entry', function () {
    it('allows student to submit journal entry after clock in', function () {
        $internship = Internship::factory()->create();
        $registration = Registration::factory()->create([
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);
        $registration->setStatus('active');

        Carbon::setTestNow(Carbon::create(2026, 4, 30, 8, 0, 0));

        $clockInAction = app(ClockInAction::class);
        $clockInAction->execute($this->student, [], '127.0.0.1');

        Carbon::setTestNow(Carbon::create(2026, 4, 30, 17, 0, 0));

        $journalAction = app(SubmitLogbookEntryAction::class);
        $journal = $journalAction->execute($this->student, [
            'content' => 'Today I learned about system architecture.',
            'learning_outcomes' => 'Understanding of layered architecture.',
        ]);

        expect($journal)
            ->toBeInstanceOf(LogbookEntry::class)
            ->and($journal->status->value)
            ->toBe('submitted');

        Carbon::setTestNow();
    });
});
