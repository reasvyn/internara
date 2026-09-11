<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('7C5WM: InternshipPolicy', function () {
    test('7C5WM-FR-LIFE-012: all five roles can list and view', function () {
        $internship = Internship::factory()->create();

        foreach (['admin', 'teacher', 'supervisor', 'student'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);
            expect(Gate::allows('viewAny', Internship::class))->toBeTrue()
                ->and(Gate::allows('view', $internship))->toBeTrue();
        }
    });

    test('7C5WM-FR-LIFE-012: create and update reserved to admin', function () {
        $internship = Internship::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Internship::class))->toBeTrue()
            ->and(Gate::allows('update', $internship))->toBeTrue();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('create', Internship::class))->toBeFalse()
            ->and(Gate::allows('update', $internship))->toBeFalse();
    });

    test('7C5WM-FR-LIFE-012: admin can delete an empty internship', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $internship = Internship::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('delete', $internship))->toBeTrue();
    });

    test('7C5WM-FR-LIFE-012: admin cannot delete with placements or registrations; teacher cannot delete', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $withPlacement = Internship::factory()->create();
        Placement::factory()->create(['internship_id' => $withPlacement->id]);

        $withRegistration = Internship::factory()->create();
        Registration::factory()->create(['internship_id' => $withRegistration->id]);

        $this->actingAs($admin);
        expect(Gate::allows('delete', $withPlacement))->toBeFalse()
            ->and(Gate::allows('delete', $withRegistration))->toBeFalse();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $empty = Internship::factory()->create();
        $this->actingAs($teacher);
        expect(Gate::allows('delete', $empty))->toBeFalse();
    });

    test('7C5WM-FR-LIFE-012: force-delete reserved to super_admin', function () {
        $internship = Internship::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('forceDelete', $internship))->toBeFalse();

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin);
        expect(Gate::allows('forceDelete', $internship))->toBeTrue();
    });
});
