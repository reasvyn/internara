<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('4HWSB: DepartmentPolicy', function () {
    test('4HWSB-FR-DEPT-009: listing and viewing are open to every authenticated user', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $department = Department::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', Department::class))->toBeTrue()
            ->and(Gate::allows('view', $department))->toBeTrue();
    });

    test('4HWSB-FR-DEPT-009: listing and viewing are open to guests', function () {
        $department = Department::factory()->create();

        expect(Gate::allows('viewAny', Department::class))->toBeTrue()
            ->and(Gate::allows('view', $department))->toBeTrue();
    });

    test('4HWSB-FR-DEPT-009: admin can create and update, student cannot', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $department = Department::factory()->create();

        $this->actingAs($admin);
        expect(Gate::allows('create', Department::class))->toBeTrue()
            ->and(Gate::allows('update', $department))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($student);
        expect(Gate::allows('create', Department::class))->toBeFalse()
            ->and(Gate::allows('update', $department))->toBeFalse();
    });

    test('4HWSB-FR-DEPT-009: admin can delete an empty department', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $department = Department::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('delete', $department))->toBeTrue();
    });

    test('4HWSB-FR-DEPT-009: admin cannot delete a department that has profiles', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $department = Department::factory()->create();
        Profile::factory()->create([
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin);

        expect(Gate::allows('delete', $department))->toBeFalse();
    });

    test('4HWSB-FR-DEPT-009: force-delete is denied even for admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $department = Department::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('forceDelete', $department))->toBeFalse();
    });

    test('T4B26-FR-RBAC-010: superadmin bypasses force-delete denial via before()', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $department = Department::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('forceDelete', $department))->toBeTrue();
    });
});
