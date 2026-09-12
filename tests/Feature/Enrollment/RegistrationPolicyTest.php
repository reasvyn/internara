<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('MBB5R: RegistrationPolicy', function () {
    test('MBB5R-FR-REG-018: allows every role on viewAny', function () {
        foreach (['admin', 'teacher', 'supervisor', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', Registration::class))->toBeTrue();
        }
    });

    test('MBB5R-FR-REG-018: allows admin and owning student on view, denies outsider', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $registration = Registration::factory()->create(['student_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $registration))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $registration))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $registration))->toBeFalse();
    });

    test('MBB5R-FR-REG-018: allows admin and student on create, denies teacher', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Registration::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', Registration::class))->toBeFalse();
    });

    test('MBB5R-FR-REG-019: reserves approve and admin delete to admin', function () {
        $registration = Registration::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('approve', $registration))->toBeTrue()
            ->and(Gate::allows('delete', $registration))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('approve', $registration))->toBeFalse();
    });

    test('MBB5R-FR-REG-018: admin can update any registration', function () {
        $registration = Registration::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        expect(Gate::allows('update', $registration))->toBeTrue();
    });

    test('MBB5R-FR-REG-018: owner can update and delete own pending registration', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $registration = Registration::factory()->pending()->create(['student_id' => $owner->id]);

        $this->actingAs($owner);

        expect(Gate::allows('update', $registration))->toBeTrue()
            ->and(Gate::allows('delete', $registration))->toBeTrue();
    });

    test('MBB5R-FR-REG-018: owner cannot update or delete non-pending registration', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $registration = Registration::factory()->active()->create(['student_id' => $owner->id]);

        $this->actingAs($owner);

        expect(Gate::allows('update', $registration))->toBeFalse()
            ->and(Gate::allows('delete', $registration))->toBeFalse();
    });

    test('MBB5R-FR-REG-018: non-owner cannot update or delete pending registration', function () {
        $registration = Registration::factory()->pending()->create();

        $outsider = User::factory()->create();
        $outsider->assignRole('student');
        $this->actingAs($outsider);

        expect(Gate::allows('update', $registration))->toBeFalse()
            ->and(Gate::allows('delete', $registration))->toBeFalse();
    });
});
