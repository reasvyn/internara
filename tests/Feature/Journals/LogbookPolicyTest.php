<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Journals\Domain\Logbook\Models\Logbook;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('LogbookPolicy', function () {
    test('allows every role on viewAny', function () {
        foreach (['admin', 'teacher', 'supervisor', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', Logbook::class))->toBeTrue();
        }
    });

    test('allows admin and owning student on view, denies outsider', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $entry = Logbook::factory()->create(['user_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $entry))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $entry))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('view', $entry))->toBeFalse();
    });

    test('student-only create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Logbook::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', Logbook::class))->toBeFalse();
    });

    test('allows owner to update a draft but not a submitted entry', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $draft = Logbook::factory()->create(['user_id' => $owner->id, 'status' => 'draft', 'date' => now()->subDay()]);
        $submitted = Logbook::factory()->create(['user_id' => $owner->id, 'status' => 'submitted', 'date' => now()]);

        $this->actingAs($owner);

        expect(Gate::allows('update', $draft))->toBeTrue()
            ->and(Gate::allows('update', $submitted))->toBeFalse()
            ->and(Gate::allows('delete', $draft))->toBeTrue()
            ->and(Gate::allows('delete', $submitted))->toBeFalse();
    });

    test('allows admin on update and delete, denies outsider student', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $entry = Logbook::factory()->create(['user_id' => $owner->id]);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $entry))->toBeTrue()
            ->and(Gate::allows('delete', $entry))->toBeTrue();

        $this->actingAs($outsider);
        expect(Gate::allows('update', $entry))->toBeFalse()
            ->and(Gate::allows('delete', $entry))->toBeFalse();
    });

    test('denies owning student on supervisor note without a proxy link', function () {
        $owner = User::factory()->create();
        $owner->assignRole('student');
        $entry = Logbook::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner);

        expect(Gate::allows('addSupervisorNote', $entry))->toBeFalse();
    });
});
