<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Actions\GetProfileFormDataAction;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('GetProfileFormDataAction', function () {
    it('returns fields for super admin', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $data = app(GetProfileFormDataAction::class)->execute($user);

        expect($data['fields'])->toContain('name', 'email', 'phone', 'address', 'bio');
        expect($data['staffFields'])->toHaveCount(5);
        expect($data['canChangeName'])->toBeFalse();
        expect($data['role'])->toBe('super_admin');
    });

    it('includes staff fields for admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $data = app(GetProfileFormDataAction::class)->execute($user);

        expect($data['staffFields'])->toContain('employment_status', 'job_title', 'employee_id_number');
        expect($data['canChangeName'])->toBeTrue();
    });

    it('includes staff fields for teacher', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $data = app(GetProfileFormDataAction::class)->execute($user);

        expect($data['staffFields'])->toHaveCount(5);
        expect($data['canChangeName'])->toBeTrue();
    });

    it('returns empty staff fields for student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        $data = app(GetProfileFormDataAction::class)->execute($user);

        expect($data['staffFields'])->toBe([]);
        expect($data['canChangeName'])->toBeTrue();
        expect($data['role'])->toBe('student');
    });

    it('returns unknown role when user has no role', function () {
        $user = User::factory()->create();

        $data = app(GetProfileFormDataAction::class)->execute($user);

        expect($data['role'])->toBe('unknown');
    });
});
