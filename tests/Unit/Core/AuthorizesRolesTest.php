<?php

declare(strict_types=1);

use App\Domain\Core\Policies\Concerns\AuthorizesRoles;
use Illuminate\Database\Eloquent\Model;

class ARTestUser extends Model
{
    protected $table = 'ar_users';

    private array $assignedRoles = [];

    public function assignRole(string $role): void
    {
        $this->assignedRoles[] = $role;
    }

    public function hasRole(string|array $role): bool
    {
        $roles = is_array($role) ? $role : [$role];

        foreach ($roles as $r) {
            if (in_array($r, $this->assignedRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }
}

class ARTestPolicy
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

    public function callCanManageAnyRole($user): bool
    {
        return $this->canManageAnyRole($user);
    }

    public function callHasAnyOfRoles($user, array $roles): bool
    {
        return $this->hasAnyOfRoles($user, $roles);
    }
}

describe('AuthorizesRoles', function () {
    it('isAdmin returns true for super_admin', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('super_admin');

        expect($policy->callIsAdmin($user))->toBeTrue();
    });

    it('isAdmin returns true for admin', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('admin');

        expect($policy->callIsAdmin($user))->toBeTrue();
    });

    it('isAdmin returns false for non-admin roles', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('teacher');

        expect($policy->callIsAdmin($user))->toBeFalse();
    });

    it('isTeacher returns true for teacher', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('teacher');

        expect($policy->callIsTeacher($user))->toBeTrue();
    });

    it('isTeacher returns false for non-teacher', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('student');

        expect($policy->callIsTeacher($user))->toBeFalse();
    });

    it('isStudent returns true for student', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('student');

        expect($policy->callIsStudent($user))->toBeTrue();
    });

    it('isSupervisor returns true for supervisor', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('supervisor');

        expect($policy->callIsSupervisor($user))->toBeTrue();
    });

    it('isAdminOrTeacher returns true for admin', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('admin');

        expect($policy->callIsAdminOrTeacher($user))->toBeTrue();
    });

    it('isAdminOrTeacher returns true for teacher', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('teacher');

        expect($policy->callIsAdminOrTeacher($user))->toBeTrue();
    });

    it('isAdminOrTeacher returns false for student', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('student');

        expect($policy->callIsAdminOrTeacher($user))->toBeFalse();
    });

    it('canManageAnyRole delegates to isAdmin', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('super_admin');

        expect($policy->callCanManageAnyRole($user))->toBeTrue();
    });

    it('hasAnyOfRoles matches any of the given roles', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('teacher');

        expect($policy->callHasAnyOfRoles($user, ['admin', 'teacher']))->toBeTrue();
    });

    it('hasAnyOfRoles returns false when no roles match', function () {
        $policy = new ARTestPolicy;
        $user = new ARTestUser;
        $user->assignRole('student');

        expect($policy->callHasAnyOfRoles($user, ['super_admin', 'admin']))->toBeFalse();
    });
});
