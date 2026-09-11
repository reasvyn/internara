<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('XI3LB: CompanyPolicy', function () {
    test('XI3LB-FR-COMP-015: listing and viewing are open to every authenticated user', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $company = Company::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', Company::class))->toBeTrue()
            ->and(Gate::allows('view', $company))->toBeTrue();
    });

    test('XI3LB-FR-COMP-015: listing and viewing are open to guests', function () {
        $company = Company::factory()->create();

        expect(Gate::allows('viewAny', Company::class))->toBeTrue()
            ->and(Gate::allows('view', $company))->toBeTrue();
    });

    test('XI3LB-FR-COMP-015: admin can create and update, student cannot', function () {
        $company = Company::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('create', Company::class))->toBeTrue()
            ->and(Gate::allows('update', $company))->toBeTrue();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        expect(Gate::allows('create', Company::class))->toBeFalse()
            ->and(Gate::allows('update', $company))->toBeFalse();
    });

    test('XI3LB-FR-COMP-015: admin can delete a company without placements', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $company = Company::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('delete', $company))->toBeTrue();
    });

    test('XI3LB-FR-COMP-015: admin cannot delete a company that has placements; teacher cannot delete', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $company = Company::factory()->create();
        Placement::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin);
        expect(Gate::allows('delete', $company))->toBeFalse();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);
        expect(Gate::allows('delete', $company))->toBeFalse();
    });

    test('XI3LB-FR-COMP-015: force-delete reserved to super_admin', function () {
        $company = Company::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        expect(Gate::allows('forceDelete', $company))->toBeFalse();

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $this->actingAs($superadmin);
        expect(Gate::allows('forceDelete', $company))->toBeTrue();
    });
});
