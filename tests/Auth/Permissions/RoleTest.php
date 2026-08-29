<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permissions\Enums\Role;

describe('QLHDO: role model — FR-G2', function () {
    test('QLHDO-FR-G2: exposes exactly five stored user roles', function () {
        expect(Role::userRoles())->toHaveCount(5)
            ->toContain(Role::SUPER_ADMIN, Role::ADMIN, Role::TEACHER, Role::STUDENT, Role::SUPERVISOR);
    });

    test('QLHDO-FR-G2: stored roles are marked as user roles', function () {
        foreach (Role::userRoles() as $role) {
            expect($role->isUserRole())->toBeTrue();
        }
    });

    test('QLHDO-FR-G2: exposes the three runtime functional roles', function () {
        expect(Role::functionalRoles())->toHaveCount(3)
            ->toContain(Role::ADMIN, Role::MENTOR, Role::MENTEE);
    });

    test('QLHDO-FR-G2: functional grouping roles are not stored user roles', function () {
        expect(Role::MENTOR->isUserRole())->toBeFalse()
            ->and(Role::MENTEE->isUserRole())->toBeFalse()
            ->and(Role::ADMIN->isUserRole())->toBeTrue();
    });

    test('QLHDO-FR-G2: resolvesTo maps functional roles to stored roles', function () {
        expect(Role::ADMIN->resolvesTo())->toContain(Role::SUPER_ADMIN, Role::ADMIN);
        expect(Role::MENTOR->resolvesTo())->toContain(Role::TEACHER, Role::SUPERVISOR);
        expect(Role::MENTEE->resolvesTo())->toContain(Role::STUDENT);
    });

    test('QLHDO-FR-G2: every case has a non-empty translated label', function () {
        foreach (Role::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });
});
