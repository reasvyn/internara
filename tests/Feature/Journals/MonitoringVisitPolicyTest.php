<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Journals\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('MonitoringVisitPolicy', function () {
    test('allows admin-group and teacher on viewAny, denies supervisor and student', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('viewAny', MonitoringVisit::class))->toBeTrue();

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);
        expect(Gate::allows('viewAny', MonitoringVisit::class))->toBeFalse();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', MonitoringVisit::class))->toBeFalse();
    });

    test('allows admin and owning teacher on view, denies other teacher', function () {
        $owner = User::factory()->create();
        $owner->assignRole('teacher');
        $visit = MonitoringVisit::factory()->create(['teacher_id' => $owner->id]);
        $other = User::factory()->create();
        $other->assignRole('teacher');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('view', $visit))->toBeTrue();

        $this->actingAs($owner);
        expect(Gate::allows('view', $visit))->toBeTrue();

        $this->actingAs($other);
        expect(Gate::allows('view', $visit))->toBeFalse();
    });

    test('allows teacher on create, denies student', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', MonitoringVisit::class))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', MonitoringVisit::class))->toBeFalse();
    });

    test('allows owner to update an unverified visit but not a verified one', function () {
        $owner = User::factory()->create();
        $owner->assignRole('teacher');
        $open = MonitoringVisit::factory()->create(['teacher_id' => $owner->id, 'is_verified' => false]);
        $closed = MonitoringVisit::factory()->create(['teacher_id' => $owner->id, 'is_verified' => true]);

        $this->actingAs($owner);

        expect(Gate::allows('update', $open))->toBeTrue()
            ->and(Gate::allows('update', $closed))->toBeFalse()
            ->and(Gate::allows('delete', $open))->toBeTrue()
            ->and(Gate::allows('delete', $closed))->toBeFalse();
    });

    test('allows admin on update and delete, denies other teacher', function () {
        $owner = User::factory()->create();
        $owner->assignRole('teacher');
        $visit = MonitoringVisit::factory()->create(['teacher_id' => $owner->id, 'is_verified' => false]);
        $other = User::factory()->create();
        $other->assignRole('teacher');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('update', $visit))->toBeTrue()
            ->and(Gate::allows('delete', $visit))->toBeTrue();

        $this->actingAs($other);
        expect(Gate::allows('update', $visit))->toBeFalse()
            ->and(Gate::allows('delete', $visit))->toBeFalse();
    });

    test('reserves verify to admin', function () {
        $visit = MonitoringVisit::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('verify', MonitoringVisit::class))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('verify', MonitoringVisit::class))->toBeFalse();
    });
});
