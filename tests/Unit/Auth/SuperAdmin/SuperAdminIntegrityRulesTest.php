<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permissions\Enums\Role;
use App\Modules\Auth\Domain\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\Permission\Models\Role as RoleModel;

describe('95EVB: super admin integrity rules', function (): void {
    $makeUser = function (string $name, string $username, array $roles, ?AccountStatus $status, int $count): User {
        $user = new User([
            'name' => $name,
            'email' => $username.'@test.local',
            'username' => $username,
            'status' => $status,
        ]);
        $user->setAttribute('super_admin_count', $count);
        $user->setRelation(
            'roles',
            new EloquentCollection(array_map(
                fn (string $role): RoleModel => new RoleModel(['name' => $role, 'guard_name' => 'web']),
                $roles,
            ))
        );

        return $user;
    };

    $makeRules = fn (bool $isSuperAdmin, string $name = 'Super Admin', string $username = 'superadmin', ?AccountStatus $status = null, int $count = 1): SuperAdminIntegrityRules => SuperAdminIntegrityRules::fromArray([
        'name' => $name,
        'username' => $username,
        'isSuperAdmin' => $isSuperAdmin,
        'status' => $status,
        'superAdminCount' => $count,
    ]);

    test('95EVB-FR-USER-047: fromModel resolves super-admin-ness through the role grant', function () use ($makeUser): void {
        $superAdmin = $makeUser('Super Admin', 'superadmin', [Role::SUPER_ADMIN->value], AccountStatus::PROTECTED, 1);
        $regular = $makeUser('Admin', 'admin', [Role::ADMIN->value], AccountStatus::VERIFIED, 1);

        $superRules = SuperAdminIntegrityRules::fromModel($superAdmin);
        $regularRules = SuperAdminIntegrityRules::fromModel($regular);

        expect($superRules->canBeDeleted())->toBeFalse();
        expect($regularRules->canBeDeleted())->toBeTrue();
        expect($superAdmin->exists)->toBeFalse();
        expect($regular->exists)->toBeFalse();
    });

    test('95EVB-FR-USER-047: fromModel maps a null status to unprotected without failing', function () use ($makeUser): void {
        $user = $makeUser('Super Admin', 'superadmin', [Role::SUPER_ADMIN->value], null, 1);

        $rules = SuperAdminIntegrityRules::fromModel($user);

        expect($rules->hasProtectedStatus())->toBeFalse();
        expect($rules->isImmutable())->toBeFalse();
        expect($user->exists)->toBeFalse();
    });

    test('95EVB-FR-USER-008: isNameValid pins the configured super-admin name', function () use ($makeRules): void {
        expect($makeRules(true, 'Super Admin')->isNameValid())->toBeTrue();
        expect($makeRules(true, 'Renamed Admin')->isNameValid())->toBeFalse();
        expect($makeRules(false, 'Anyone')->isNameValid())->toBeTrue();
    });

    test('95EVB-FR-USER-008: isUsernameValid pins the configured super-admin username', function () use ($makeRules): void {
        expect($makeRules(true, 'Super Admin', 'superadmin')->isUsernameValid())->toBeTrue();
        expect($makeRules(true, 'Super Admin', 'renamed')->isUsernameValid())->toBeFalse();
        expect($makeRules(false, 'Anyone', 'anyone')->isUsernameValid())->toBeTrue();
    });

    test('reports whether this account is the last remaining super admin', function () use ($makeRules): void {
        expect($makeRules(true, count: 1)->isLastSuperAdmin())->toBeTrue();
        expect($makeRules(true, count: 2)->isLastSuperAdmin())->toBeFalse();
        expect($makeRules(false, count: 0)->isLastSuperAdmin())->toBeFalse();
    });

    test('95EVB-FR-USER-009: canBeDeleted refuses the super admin unconditionally', function () use ($makeRules): void {
        expect($makeRules(true, count: 5)->canBeDeleted())->toBeFalse();
        expect($makeRules(false)->canBeDeleted())->toBeTrue();
    });

    test('95EVB-FR-USER-052: canBeLocked refuses the super admin unconditionally', function () use ($makeRules): void {
        expect($makeRules(true)->canBeLocked())->toBeFalse();
        expect($makeRules(false)->canBeLocked())->toBeTrue();
    });

    test('95EVB-FR-USER-051: canChangeName and canChangeUsername freeze super-admin identity', function () use ($makeRules): void {
        expect($makeRules(true)->canChangeName())->toBeFalse();
        expect($makeRules(true)->canChangeUsername())->toBeFalse();
        expect($makeRules(false)->canChangeName())->toBeTrue();
        expect($makeRules(false)->canChangeUsername())->toBeTrue();
    });

    test('exposes protected-status and immutability only for protected super admins', function () use ($makeRules): void {
        expect($makeRules(true, status: AccountStatus::PROTECTED)->hasProtectedStatus())->toBeTrue();
        expect($makeRules(true, status: AccountStatus::PROTECTED)->isImmutable())->toBeTrue();
        expect($makeRules(true, status: AccountStatus::VERIFIED)->hasProtectedStatus())->toBeFalse();
        expect($makeRules(true, status: AccountStatus::VERIFIED)->isImmutable())->toBeFalse();
        expect($makeRules(false, status: AccountStatus::VERIFIED)->hasProtectedStatus())->toBeTrue();
        expect($makeRules(false)->isImmutable())->toBeFalse();
    });

    test('reports single-super-admin and missing-super-admin system states', function () use ($makeRules): void {
        expect($makeRules(true, count: 1)->isSingleSuperAdmin())->toBeTrue();
        expect($makeRules(true, count: 2)->isSingleSuperAdmin())->toBeFalse();
        expect($makeRules(false, count: 0)->needsSuperAdmin())->toBeTrue();
        expect($makeRules(false, count: 1)->needsSuperAdmin())->toBeFalse();
        expect($makeRules(true, count: 0)->needsSuperAdmin())->toBeFalse();
    });

    test('95EVB-FR-USER-008: fromArray rejects a missing name', function (): void {
        expect(fn (): SuperAdminIntegrityRules => SuperAdminIntegrityRules::fromArray([
            'username' => 'superadmin',
            'isSuperAdmin' => true,
            'status' => null,
            'superAdminCount' => 1,
        ]))->toThrow(InvalidArgumentException::class, 'name');
    });

    test('95EVB-FR-USER-008: toArray, equals, and with round-trip by value', function () use ($makeRules): void {
        $rules = $makeRules(true);

        expect($rules->toArray()['username'])->toBe('superadmin');
        expect($rules->equals($makeRules(true)))->toBeTrue();
        expect($rules->equals($makeRules(false)))->toBeFalse();

        $renamed = $rules->with('name', 'Renamed Admin');

        expect($renamed->isNameValid())->toBeFalse();
        expect($rules->isNameValid())->toBeTrue();
    });
});
