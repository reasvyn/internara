<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('920SO: AccountApplicationPolicy', function () {
    test('920SO-FR-APPLY-016: admin allowed on viewAny, student denied', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('viewAny', AccountApplication::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', AccountApplication::class))->toBeFalse();
    });

    test('920SO-FR-APPLY-016: admin and email owner can view, outsider cannot', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $application = AccountApplication::factory()->create(['email' => $owner->email]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $application))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $application))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $application))->toBeFalse();
    });

    test('920SO-FR-APPLY-016: create is open to any authenticated user', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', AccountApplication::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', AccountApplication::class))->toBeTrue();
    });

    test('920SO-FR-APPLY-016: update and delete reserved to admin', function () {
        $application = AccountApplication::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $application))->toBeTrue()
            ->and(Gate::allows('delete', $application))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('update', $application))->toBeFalse()
            ->and(Gate::allows('delete', $application))->toBeFalse();
    });
});
