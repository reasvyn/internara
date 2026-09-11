<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permissions\Enums\Role;

describe('T4B26: Role enum', function (): void {
    test('T4B26-FR-RBAC-003: cases carry the specified backing values', function (): void {
        expect(Role::SUPER_ADMIN->value)->toBe('superadmin');
        expect(Role::from('superadmin'))->toBe(Role::SUPER_ADMIN);
        expect(Role::ADMIN->value)->toBe('admin');
        expect(Role::from('admin'))->toBe(Role::ADMIN);
        expect(Role::TEACHER->value)->toBe('teacher');
        expect(Role::from('teacher'))->toBe(Role::TEACHER);
        expect(Role::STUDENT->value)->toBe('student');
        expect(Role::from('student'))->toBe(Role::STUDENT);
        expect(Role::SUPERVISOR->value)->toBe('supervisor');
        expect(Role::from('supervisor'))->toBe(Role::SUPERVISOR);
        expect(Role::MENTOR->value)->toBe('func_mentor');
        expect(Role::from('func_mentor'))->toBe(Role::MENTOR);
        expect(Role::MENTEE->value)->toBe('func_mentee');
        expect(Role::from('func_mentee'))->toBe(Role::MENTEE);
        expect(Role::cases())->toHaveCount(7);
        expect(Role::tryFrom('no-such-value'))->toBeNull();
    });
    test('T4B26-FR-RBAC-003: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(Role::SUPER_ADMIN->label())->toBe('Super Administrator');
        expect(Role::ADMIN->label())->toBe('Administrator');
        expect(Role::TEACHER->label())->toBe('Teacher');
        expect(Role::STUDENT->label())->toBe('Student');
        expect(Role::SUPERVISOR->label())->toBe('Supervisor');
        expect(Role::MENTOR->label())->toBe('Mentor');
        expect(Role::MENTEE->label())->toBe('Mentee');
    });

    test('T4B26-FR-RBAC-003: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(Role::SUPER_ADMIN->label())->toBe('Super Admin');
        expect(Role::ADMIN->label())->toBe('Admin');
        expect(Role::TEACHER->label())->toBe('Guru Pembimbing');
        expect(Role::STUDENT->label())->toBe('Siswa');
        expect(Role::SUPERVISOR->label())->toBe('Pembimbing Industri');
        expect(Role::MENTOR->label())->toBe('Pembimbing');
        expect(Role::MENTEE->label())->toBe('Terbimbing');
    });
    test('T4B26-FR-RBAC-003: super admin value carries no underscore', function (): void {
        expect(Role::SUPER_ADMIN->value)->toBe('superadmin');
        expect(Role::from('superadmin'))->toBe(Role::SUPER_ADMIN);
    });

    test('T4B26-FR-RBAC-004: role group listings separate stored from functional roles', function (): void {
        expect(Role::userRoles())->toBe([Role::SUPER_ADMIN, Role::ADMIN, Role::TEACHER, Role::STUDENT, Role::SUPERVISOR]);
        expect(Role::excludeSuperAdmin())->toBe([Role::ADMIN, Role::TEACHER, Role::STUDENT, Role::SUPERVISOR]);
        expect(Role::excludeAdmin())->toBe([Role::TEACHER, Role::STUDENT, Role::SUPERVISOR]);
        expect(Role::functionalRoles())->toBe([Role::ADMIN, Role::MENTOR, Role::MENTEE]);
    });

    test('T4B26-FR-RBAC-004: isUserRole and isFunctionalRole split on the group lists', function (): void {
        expect(Role::TEACHER->isUserRole())->toBeTrue();
        expect(Role::MENTOR->isUserRole())->toBeFalse();
        expect(Role::MENTOR->isFunctionalRole())->toBeTrue();
        expect(Role::ADMIN->isFunctionalRole())->toBeTrue();
        expect(Role::TEACHER->isFunctionalRole())->toBeFalse();
        expect(Role::STUDENT->isUserRole())->toBeTrue();
    });

    test('T4B26-FR-RBAC-004: resolvesTo maps each functional role to its stored roles', function (): void {
        expect(Role::ADMIN->resolvesTo())->toBe([Role::SUPER_ADMIN, Role::ADMIN]);
        expect(Role::MENTOR->resolvesTo())->toBe([Role::TEACHER, Role::SUPERVISOR]);
        expect(Role::MENTEE->resolvesTo())->toBe([Role::STUDENT]);
        expect(Role::TEACHER->resolvesTo())->toBe([Role::TEACHER]);
        expect(Role::SUPER_ADMIN->resolvesTo())->toBe([Role::SUPER_ADMIN]);
    });

    test('T4B26-FR-RBAC-004: functionalRolesFor maps each stored role to its functional roles', function (): void {
        expect(Role::functionalRolesFor(Role::SUPER_ADMIN))->toBe([Role::ADMIN]);
        expect(Role::functionalRolesFor(Role::ADMIN))->toBe([Role::ADMIN]);
        expect(Role::functionalRolesFor(Role::TEACHER))->toBe([Role::MENTOR]);
        expect(Role::functionalRolesFor(Role::SUPERVISOR))->toBe([Role::MENTOR]);
        expect(Role::functionalRolesFor(Role::STUDENT))->toBe([Role::MENTEE]);
        expect(Role::functionalRolesFor(Role::MENTOR))->toBe([]);
        expect(Role::functionalRolesFor(Role::MENTEE))->toBe([]);
    });

    test('T4B26-FR-RBAC-005: is() matches admin-group, mentor, and mentee without branches', function (): void {
        expect(Role::SUPER_ADMIN->is(Role::ADMIN))->toBeTrue();
        expect(Role::ADMIN->is(Role::ADMIN))->toBeTrue();
        expect(Role::TEACHER->is(Role::ADMIN))->toBeFalse();
        expect(Role::TEACHER->is(Role::MENTOR))->toBeTrue();
        expect(Role::SUPERVISOR->is(Role::MENTOR))->toBeTrue();
        expect(Role::STUDENT->is(Role::MENTOR))->toBeFalse();
        expect(Role::STUDENT->is(Role::MENTEE))->toBeTrue();
        expect(Role::TEACHER->is(Role::MENTEE))->toBeFalse();
    });
});
