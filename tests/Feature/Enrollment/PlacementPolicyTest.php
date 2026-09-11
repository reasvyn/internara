<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('PlacementPolicy', function () {
    test('allows admin on viewAny, view, create, and update; denies student', function () {
        $placement = Placement::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('viewAny', Placement::class))->toBeTrue()
            ->and(Gate::allows('view', $placement))->toBeTrue()
            ->and(Gate::allows('create', Placement::class))->toBeTrue()
            ->and(Gate::allows('update', $placement))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('viewAny', Placement::class))->toBeFalse()
            ->and(Gate::allows('view', $placement))->toBeFalse()
            ->and(Gate::allows('create', Placement::class))->toBeFalse()
            ->and(Gate::allows('update', $placement))->toBeFalse();
    });

    test('allows admin to delete an empty placement', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $placement = Placement::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('delete', $placement))->toBeTrue();
    });

    test('denies admin delete when registrations exist and denies teacher delete', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $placement = Placement::factory()->create();
        Registration::factory()->create(['placement_id' => $placement->id]);

        $this->actingAs($admin);
        expect(Gate::allows('delete', $placement))->toBeFalse();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('delete', $placement))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $placement = Placement::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', Placement::class))->toBeTrue()
            ->and(Gate::allows('delete', $placement))->toBeTrue();
    });
});
