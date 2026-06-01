<?php

declare(strict_types=1);

use App\Domain\Core\Policies\Concerns\AuthorizesRoles;
use Illuminate\Database\Eloquent\Model;

class ARUser extends Model
{
    protected $table = 'ar_users';

    private array $roles = [];

    public function assignRole(string $role): void
    {
        $this->roles[] = $role;
    }

    public function hasRole(string|array $role): bool
    {
        $roles = is_array($role) ? $role : [$role];

        return ! empty(array_intersect($roles, $this->roles));
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }
}

class ARPolicy
{
    use AuthorizesRoles;

    public function callIsAdmin($user): bool
    {
        return $this->isAdmin($user);
    }

    public function callIsTeacher($user): bool
    {
        return $this->isTeacher($user);
    }

    public function callIsStudent($user): bool
    {
        return $this->isStudent($user);
    }

    public function callIsSupervisor($user): bool
    {
        return $this->isSupervisor($user);
    }

    public function callIsAdminOrTeacher($user): bool
    {
        return $this->isAdminOrTeacher($user);
    }

    public function callHasAnyOfRoles($user, array $roles): bool
    {
        return $this->hasAnyOfRoles($user, $roles);
    }
}

describe('AuthorizesRoles', function () {
    it('isAdmin returns true for super_admin and admin', function () {
        $policy = new ARPolicy;
        $admin = tap(new ARUser)->assignRole('admin');
        $super = tap(new ARUser)->assignRole('super_admin');

        expect($policy->callIsAdmin($admin))->toBeTrue()
            ->and($policy->callIsAdmin($super))->toBeTrue();
    });

    it('isAdmin returns false for teacher', function () {
        $policy = new ARPolicy;
        $user = tap(new ARUser)->assignRole('teacher');

        expect($policy->callIsAdmin($user))->toBeFalse();
    });

    it('isTeacher returns true for teacher', function () {
        $policy = new ARPolicy;
        $user = tap(new ARUser)->assignRole('teacher');

        expect($policy->callIsTeacher($user))->toBeTrue();
    });

    it('isStudent returns true for student', function () {
        $policy = new ARPolicy;
        $user = tap(new ARUser)->assignRole('student');

        expect($policy->callIsStudent($user))->toBeTrue();
    });

    it('isSupervisor returns true for supervisor', function () {
        $policy = new ARPolicy;
        $user = tap(new ARUser)->assignRole('supervisor');

        expect($policy->callIsSupervisor($user))->toBeTrue();
    });

    it('isAdminOrTeacher returns true for admin or teacher', function () {
        $policy = new ARPolicy;

        expect($policy->callIsAdminOrTeacher(tap(new ARUser)->assignRole('admin')))->toBeTrue()
            ->and($policy->callIsAdminOrTeacher(tap(new ARUser)->assignRole('teacher')))->toBeTrue();
    });

    it('hasAnyOfRoles matches any of the given roles', function () {
        $policy = new ARPolicy;
        $user = tap(new ARUser)->assignRole('teacher');

        expect($policy->callHasAnyOfRoles($user, ['admin', 'teacher']))->toBeTrue()
            ->and($policy->callHasAnyOfRoles($user, ['super_admin', 'admin']))->toBeFalse();
    });
});
