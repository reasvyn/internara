<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('AttendancePolicy', function () {
    test('allows every role on viewAny', function () {
        foreach (['admin', 'teacher', 'supervisor', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', Attendance::class))->toBeTrue();
        }
    });

    test('allows admin and owning student on view, denies outsider', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $log = Attendance::factory()->create(['user_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $log))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $log))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $log))->toBeFalse();
    });

    test('student-only create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Attendance::class))->toBeTrue();

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);
        expect(Gate::allows('create', Attendance::class))->toBeFalse();
    });

    test('allows admin on verify, denies student and supervisor without proxy', function () {
        $log = Attendance::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('verify', $log))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('verify', $log))->toBeFalse();

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);
        expect(Gate::allows('verify', $log))->toBeFalse();
    });

    test('reserves update and delete to admin', function () {
        $log = Attendance::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $log))->toBeTrue()
            ->and(Gate::allows('delete', $log))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('update', $log))->toBeFalse()
            ->and(Gate::allows('delete', $log))->toBeFalse();
    });
});
