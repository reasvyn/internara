<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Internship\Models\Internship;
use App\Domain\School\Actions\ActivateAcademicYearAction;
use App\Domain\School\Actions\BulkDeleteAcademicYearsAction;
use App\Domain\School\Actions\DeleteAcademicYearAction;
use App\Domain\School\Actions\DeleteDepartmentAction;
use App\Domain\School\Actions\UpdateSchoolAction;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
});

describe('UpdateSchoolAction', function () {
    it('updates school with logo upload', function () {
        $school = School::factory()->create();
        $logo = UploadedFile::fake()->image('school-logo.png');

        $updated = app(UpdateSchoolAction::class)->execute($school, [
            'name' => 'School With Logo',
            'logo_file' => $logo,
        ]);

        expect($updated->name)->toBe('School With Logo');
    });
});

describe('DeleteDepartmentAction', function () {
    it('blocks delete when department has profiles', function () {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['department_id' => $department->id]);

        app(DeleteDepartmentAction::class)->execute($department);
    })->throws(RejectedException::class);

    it('deletes department without profiles', function () {
        $department = Department::factory()->create();

        app(DeleteDepartmentAction::class)->execute($department);

        expect(Department::find($department->id))->toBeNull();
    });
});

describe('DeleteAcademicYearAction', function () {
    it('blocks delete when year is active', function () {
        $year = AcademicYear::factory()->create(['is_active' => true]);

        app(DeleteAcademicYearAction::class)->execute($year);
    })->throws(RejectedException::class);

    it('blocks delete when year has related internships', function () {
        $year = AcademicYear::factory()->create(['is_active' => false]);
        Internship::factory()->create(['academic_year_id' => $year->id]);

        app(DeleteAcademicYearAction::class)->execute($year);
    })->throws(RejectedException::class);
});

describe('ActivateAcademicYearAction', function () {
    it('deactivates previously active year', function () {
        $oldYear = AcademicYear::factory()->create(['is_active' => true]);
        $newYear = AcademicYear::factory()->create(['is_active' => false]);

        app(ActivateAcademicYearAction::class)->execute($newYear);

        expect($oldYear->fresh()->is_active)->toBeFalse()
            ->and($newYear->fresh()->is_active)->toBeTrue();
    });

    it('blocks when year is already active', function () {
        $year = AcademicYear::factory()->create(['is_active' => true]);

        app(ActivateAcademicYearAction::class)->execute($year);
    })->throws(RejectedException::class);
});

describe('BulkDeleteAcademicYearsAction', function () {
    it('blocks when any included year is active', function () {
        $active = AcademicYear::factory()->create(['is_active' => true]);
        $inactive = AcademicYear::factory()->create(['is_active' => false]);

        app(BulkDeleteAcademicYearsAction::class)->execute([$active->id, $inactive->id]);
    })->throws(RejectedException::class);

    it('deletes only inactive years in batch', function () {
        $year = AcademicYear::factory()->create(['is_active' => false]);

        $count = app(BulkDeleteAcademicYearsAction::class)->execute([$year->id]);

        expect($count)->toBe(1)
            ->and(AcademicYear::find($year->id))->toBeNull();
    });

    it('returns 0 for already deleted ids', function () {
        $count = app(BulkDeleteAcademicYearsAction::class)->execute(['nonexistent-id']);

        expect($count)->toBe(0);
    });
});
