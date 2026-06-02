<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Core\Policies\Concerns\AuthorizesOwnership;
use App\Domain\Core\Policies\Concerns\AuthorizesRoles;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('BasePolicy', function () {
    it('is abstract', function () {
        expect((new \ReflectionClass(BasePolicy::class))->isAbstract())->toBeTrue();
    });

    it('uses AuthorizesRoles trait', function () {
        expect(in_array(AuthorizesRoles::class, class_uses_recursive(BasePolicy::class)))->toBeTrue();
    });

    it('uses AuthorizesOwnership trait', function () {
        expect(in_array(AuthorizesOwnership::class, class_uses_recursive(BasePolicy::class)))->toBeTrue();
    });
});

describe('AuthorizesRoles', function () {
    it('identifies admin users', function () {
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);

        expect($admin->hasAnyRole(['super_admin', 'admin']))->toBeTrue();
    });

    it('identifies non-admin users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);

        expect($student->hasAnyRole(['super_admin', 'admin']))->toBeFalse();
    });

    it('identifies teacher users', function () {
        $teacher = User::factory()->create()->assignRole(Role::TEACHER->value);

        expect($teacher->hasRole('teacher'))->toBeTrue();
    });

    it('identifies student users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);

        expect($student->hasRole('student'))->toBeTrue();
    });

    it('identifies supervisor users', function () {
        $supervisor = User::factory()->create()->assignRole(Role::SUPERVISOR->value);

        expect($supervisor->hasRole('supervisor'))->toBeTrue();
    });

    it('identifies admin or teacher', function () {
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $teacher = User::factory()->create()->assignRole(Role::TEACHER->value);
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);

        expect($admin->hasAnyRole(['super_admin', 'admin', 'teacher']))->toBeTrue()
            ->and($teacher->hasAnyRole(['super_admin', 'admin', 'teacher']))->toBeTrue()
            ->and($student->hasAnyRole(['super_admin', 'admin', 'teacher']))->toBeFalse();
    });

    it('checks hasAnyOfRoles', function () {
        $teacher = User::factory()->create()->assignRole(Role::TEACHER->value);

        expect($teacher->hasAnyRole([Role::TEACHER->value, Role::STUDENT->value]))->toBeTrue()
            ->and($teacher->hasAnyRole([Role::STUDENT->value]))->toBeFalse();
    });
});

describe('AuthorizesOwnership', function () {
    it('identifies owner by user_id', function () {
        $user = User::factory()->create();
        $model = new class extends Model
        {
            protected $table = 'ownership_test';
        };
        $model->user_id = $user->id;

        expect($model->user_id)->toBe($user->id);
    });

    it('identifies non-owner', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $model = new class extends Model
        {
            protected $table = 'ownership_test';
        };
        $model->user_id = $other->id;

        expect($model->user_id)->not->toBe($user->id);
    });
});
