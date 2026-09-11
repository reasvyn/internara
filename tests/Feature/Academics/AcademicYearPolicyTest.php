<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('XW6F5: AcademicYearPolicy', function () {
    test('XW6F5-FR-YEAR-013: open listing and viewing to every authenticated user', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $year = AcademicYear::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', AcademicYear::class))->toBeTrue()
            ->and(Gate::allows('view', $year))->toBeTrue();
    });

    test('XW6F5-FR-YEAR-013: admin can create and update', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $year = AcademicYear::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('create', AcademicYear::class))->toBeTrue()
            ->and(Gate::allows('update', $year))->toBeTrue();
    });

    test('XW6F5-FR-YEAR-013: student cannot create or update', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $year = AcademicYear::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('create', AcademicYear::class))->toBeFalse()
            ->and(Gate::allows('update', $year))->toBeFalse();
    });

    test('XW6F5-FR-YEAR-013: activate and delete are denied even for admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $year = AcademicYear::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('activate', $year))->toBeFalse()
            ->and(Gate::allows('delete', $year))->toBeFalse();
    });

    test('T4B26-FR-RBAC-010: superadmin bypasses hard-deny via before()', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $year = AcademicYear::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('delete', $year))->toBeTrue()
            ->and(Gate::allows('activate', $year))->toBeTrue();
    });
});
