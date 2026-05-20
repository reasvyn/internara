<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Contracts\LabelEnum;

describe('Role enum', function () {
    it('is string-backed', function () {
        expect(Role::SUPER_ADMIN->value)->toBe('super_admin');
    });

    it('implements LabelEnum', function () {
        expect(Role::SUPER_ADMIN)->toBeInstanceOf(LabelEnum::class);
    });

    it('has user roles and functional roles', function () {
        expect(Role::userRoles())->toHaveCount(5);
        expect(Role::functionalRoles())->toHaveCount(3);
    });

    it('identifies user roles', function () {
        expect(Role::SUPER_ADMIN->isUserRole())->toBeTrue()
            ->and(Role::MENTOR->isUserRole())->toBeFalse();
    });

    it('resolves functional roles', function () {
        expect(Role::ADMIN->resolvesTo())->toContain(Role::SUPER_ADMIN, Role::ADMIN);
        expect(Role::MENTOR->resolvesTo())->toContain(Role::TEACHER, Role::SUPERVISOR);
        expect(Role::MENTEE->resolvesTo())->toContain(Role::STUDENT);
    });
});
