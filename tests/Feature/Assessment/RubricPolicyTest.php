<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('RubricPolicy', function () {
    test('allows admin on every ability', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $rubric = Rubric::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('viewAny', Rubric::class))->toBeTrue()
            ->and(Gate::allows('view', $rubric))->toBeTrue()
            ->and(Gate::allows('create', Rubric::class))->toBeTrue()
            ->and(Gate::allows('update', $rubric))->toBeTrue()
            ->and(Gate::allows('delete', $rubric))->toBeTrue();
    });

    test('denies teacher on every ability', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $rubric = Rubric::factory()->create();

        $this->actingAs($teacher);

        expect(Gate::allows('viewAny', Rubric::class))->toBeFalse()
            ->and(Gate::allows('view', $rubric))->toBeFalse()
            ->and(Gate::allows('create', Rubric::class))->toBeFalse()
            ->and(Gate::allows('update', $rubric))->toBeFalse()
            ->and(Gate::allows('delete', $rubric))->toBeFalse();
    });

    test('denies student on every ability', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $rubric = Rubric::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', Rubric::class))->toBeFalse()
            ->and(Gate::allows('view', $rubric))->toBeFalse()
            ->and(Gate::allows('create', Rubric::class))->toBeFalse()
            ->and(Gate::allows('update', $rubric))->toBeFalse()
            ->and(Gate::allows('delete', $rubric))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $rubric = Rubric::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', Rubric::class))->toBeTrue()
            ->and(Gate::allows('delete', $rubric))->toBeTrue();
    });
});
