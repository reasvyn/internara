<?php

declare(strict_types=1);

use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\User\Domain\Profile\Actions\ReadProfileFormAction;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| OCEMS — Profile Management — ReadProfileFormAction (spec-driven)
|--------------------------------------------------------------------------
| Covers: FR-RP1..RP3, FR-PE2 (via integration)
|
| Justification: ReadProfileFormAction is the single source for form field
| visibility; ProfileEditor delegates to it (FR-PE2). Tests verify role-aware
| branching per PS-2 and super admin protection (canChange*).
*/

describe('OCEMS: ReadProfileFormAction', function (): void {
    beforeEach(function (): void {
        Role::findOrCreate('student', 'web');
        Role::findOrCreate('supervisor', 'web');
        Role::findOrCreate('teacher', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('super_admin', 'web');
    });

    test('OCEMS-FR-RP1: returns fields always including name email phone address bio', function (): void {
        $user = User::factory()->create();
        $user->assignRole('student');
        $result = app(ReadProfileFormAction::class)->execute($user);

        expect($result['fields'])->toBe(['name', 'email', 'phone', 'address', 'bio']);
    });

    test('OCEMS-FR-RP2: returns staffFields only for staff roles', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $super = User::factory()->create();
        $super->assignRole('super_admin');

        $rStudent = app(ReadProfileFormAction::class)->execute($student);
        $rSupervisor = app(ReadProfileFormAction::class)->execute($supervisor);
        $rTeacher = app(ReadProfileFormAction::class)->execute($teacher);
        $rAdmin = app(ReadProfileFormAction::class)->execute($admin);
        $rSuper = app(ReadProfileFormAction::class)->execute($super);

        expect($rStudent['staffFields'])->toBeEmpty()
            ->and($rSupervisor['staffFields'])->toBeEmpty()
            ->and($rTeacher['staffFields'])->toBe(['employment_status', 'job_title', 'id_number', 'competence_field'])
            ->and($rAdmin['staffFields'])->toBe(['employment_status', 'job_title', 'id_number', 'competence_field'])
            ->and($rSuper['staffFields'])->toBe(['employment_status', 'job_title', 'id_number', 'competence_field']);
    });

    test('OCEMS-FR-RP3: returns canChangeName/canChangeUsername false for super_admin', function (): void {
        $super = User::factory()->create();
        $super->assignRole('super_admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->create();
        $student->assignRole('student');

        $rSuper = app(ReadProfileFormAction::class)->execute($super);
        $rAdmin = app(ReadProfileFormAction::class)->execute($admin);
        $rStudent = app(ReadProfileFormAction::class)->execute($student);

        expect($rSuper['canChangeName'])->toBeFalse()
            ->and($rSuper['canChangeUsername'])->toBeFalse()
            ->and($rAdmin['canChangeName'])->toBeTrue()
            ->and($rAdmin['canChangeUsername'])->toBeTrue()
            ->and($rStudent['canChangeName'])->toBeTrue();
    });

    test('OCEMS-FR-RP1+RP2+RP3: returns role string', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $result = app(ReadProfileFormAction::class)->execute($teacher);
        expect($result['role'])->toBe('teacher');
    });

    test('OCEMS-FR-RP1: extends BaseReadAction', function (): void {
        expect(new ReadProfileFormAction)->toBeInstanceOf(BaseReadAction::class);
    });

    test('OCEMS-NFR-M1: declares strict_types', function (): void {
        $source = file_get_contents((new ReflectionClass(ReadProfileFormAction::class))->getFileName());
        expect($source)->toContain('declare(strict_types=1)');
    });
});
