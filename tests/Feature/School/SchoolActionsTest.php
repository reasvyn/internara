<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\School\Actions\ActivateAcademicYearAction;
use App\Domain\School\Actions\BulkDeleteAcademicYearsAction;
use App\Domain\School\Actions\CreateAcademicYearAction;
use App\Domain\School\Actions\CreateDepartmentAction;
use App\Domain\School\Actions\DeleteAcademicYearAction;
use App\Domain\School\Actions\DeleteDepartmentAction;
use App\Domain\School\Actions\UpdateAcademicYearAction;
use App\Domain\School\Actions\UpdateDepartmentAction;
use App\Domain\School\Actions\UpdateSchoolAction;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
});

describe('CreateDepartmentAction', function () {
    it('creates a department', function () {
        $school = School::factory()->create();

        $department = app(CreateDepartmentAction::class)->execute([
            'name' => 'Computer Science',
            'school_id' => $school->id,
        ]);

        expect($department)->toBeInstanceOf(Department::class)
            ->and($department->name)->toBe('Computer Science')
            ->and($department->school_id)->toBe($school->id);
    });
});

describe('UpdateDepartmentAction', function () {
    it('updates a department', function () {
        $department = Department::factory()->create();

        $updated = app(UpdateDepartmentAction::class)->execute($department, [
            'name' => 'Updated Department',
        ]);

        expect($updated->name)->toBe('Updated Department');
    });
});

describe('DeleteDepartmentAction', function () {
    it('deletes a department with no active profiles', function () {
        $department = Department::factory()->create();

        app(DeleteDepartmentAction::class)->execute($department);

        expect(Department::find($department->id))->toBeNull();
    });
});

describe('CreateAcademicYearAction', function () {
    it('creates an academic year', function () {
        $year = app(CreateAcademicYearAction::class)->execute([
            'name' => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);

        expect($year)->toBeInstanceOf(AcademicYear::class)
            ->and($year->name)->toBe('2024/2025')
            ->and($year->is_active)->toBeFalse();
    });
});

describe('UpdateAcademicYearAction', function () {
    it('updates an academic year', function () {
        $year = AcademicYear::factory()->create();

        $updated = app(UpdateAcademicYearAction::class)->execute($year, [
            'name' => '2025/2026',
        ]);

        expect($updated->name)->toBe('2025/2026');
    });
});

describe('DeleteAcademicYearAction', function () {
    it('deletes an academic year that can be deleted', function () {
        $year = AcademicYear::factory()->create(['is_active' => false]);

        app(DeleteAcademicYearAction::class)->execute($year);

        expect(AcademicYear::find($year->id))->toBeNull();
    });
});

describe('BulkDeleteAcademicYearsAction', function () {
    it('bulk deletes academic years', function () {
        $year1 = AcademicYear::factory()->create(['is_active' => false]);
        $year2 = AcademicYear::factory()->create(['is_active' => false]);

        $count = app(BulkDeleteAcademicYearsAction::class)->execute([$year1->id, $year2->id]);

        expect($count)->toBe(2)
            ->and(AcademicYear::find($year1->id))->toBeNull()
            ->and(AcademicYear::find($year2->id))->toBeNull();
    });

    it('returns 0 for empty ids', function () {
        $count = app(BulkDeleteAcademicYearsAction::class)->execute([]);

        expect($count)->toBe(0);
    });
});

describe('ActivateAcademicYearAction', function () {
    it('activates an academic year', function () {
        $year = AcademicYear::factory()->create(['is_active' => false]);

        $activated = app(ActivateAcademicYearAction::class)->execute($year);

        expect($activated->is_active)->toBeTrue();
    });
});

describe('UpdateSchoolAction', function () {
    it('updates school profile', function () {
        $school = School::factory()->create();

        $updated = app(UpdateSchoolAction::class)->execute($school, [
            'name' => 'Updated School Name',
            'phone' => '021999999',
        ]);

        expect($updated->name)->toBe('Updated School Name')
            ->and($updated->phone)->toBe('021999999');
    });
});
