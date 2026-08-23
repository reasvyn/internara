<?php

declare(strict_types=1);

use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Livewire\AttendanceManager;
use App\Journals\Attendance\Models\Attendance;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('1KSWL: AttendanceManager update & delete wiring', function () {
    test('1KSWL-FR-AT7: admin updates an attendance status via updateAttendance', function () {
        actingAsAdmin();

        $attendance = Attendance::factory()->create(['status' => AttendanceStatus::PRESENT]);

        Livewire::test(AttendanceManager::class)
            ->call('updateAttendance', $attendance->id, 'late');

        expect($attendance->fresh()->status)->toBe(AttendanceStatus::LATE);
    });

    test('1KSWL-FR-AT7: admin deletes an attendance record via deleteAttendance', function () {
        actingAsAdmin();

        $attendance = Attendance::factory()->create();

        Livewire::test(AttendanceManager::class)
            ->call('deleteAttendance', $attendance->id);

        expect(Attendance::find($attendance->id))->toBeNull();
    });

    test('1KSWL-FR-AT7: non-admin cannot update attendance (policy denies)', function () {
        actingAsStudent();

        $attendance = Attendance::factory()->create(['status' => AttendanceStatus::PRESENT]);

        Livewire::test(AttendanceManager::class)
            ->call('updateAttendance', $attendance->id, 'late');

        expect($attendance->fresh()->status)->toBe(AttendanceStatus::PRESENT);
    });

    test('1KSWL-FR-AT7: non-admin cannot delete attendance (policy denies)', function () {
        actingAsStudent();

        $attendance = Attendance::factory()->create();

        Livewire::test(AttendanceManager::class)
            ->call('deleteAttendance', $attendance->id);

        expect(Attendance::find($attendance->id))->not->toBeNull();
    });

    test('1KSWL-FR-AT5: updateAttendance rejects invalid status values', function () {
        actingAsAdmin();

        $attendance = Attendance::factory()->create(['status' => AttendanceStatus::PRESENT]);

        Livewire::test(AttendanceManager::class)
            ->call('updateAttendance', $attendance->id, 'hacked')
            ->assertHasErrors();

        expect($attendance->fresh()->status)->toBe(AttendanceStatus::PRESENT);
    });
});
