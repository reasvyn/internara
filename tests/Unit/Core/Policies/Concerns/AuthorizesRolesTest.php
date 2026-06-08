<?php

declare(strict_types=1);

use App\Core\Policies\Concerns\AuthorizesRoles;
use Illuminate\Database\Eloquent\Model;

class RolesPolicy
{
    use AuthorizesRoles;

    public function callIsAdmin(Model $user): bool
    {
        return $this->isAdmin($user);
    }

    public function callIsTeacher(Model $user): bool
    {
        return $this->isTeacher($user);
    }

    public function callIsStudent(Model $user): bool
    {
        return $this->isStudent($user);
    }

    public function callIsSupervisor(Model $user): bool
    {
        return $this->isSupervisor($user);
    }

    public function callIsAdminOrTeacher(Model $user): bool
    {
        return $this->isAdminOrTeacher($user);
    }

    public function callCanManageAnyRole(Model $user): bool
    {
        return $this->canManageAnyRole($user);
    }

    public function callHasAnyOfRoles(Model $user, array $roles): bool
    {
        return $this->hasAnyOfRoles($user, $roles);
    }
}

class UserWithRoles extends Model
{
    protected array $roleMap = [];

    protected array $anyRoleMap = [];

    public function setRoleResult(string $role, bool $result): void
    {
        $this->roleMap[$role] = $result;
    }

    public function setAnyRoleResult(array $roles, bool $result): void
    {
        $this->anyRoleMap[implode(',', $roles)] = $result;
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        $role = is_array($roles) ? $roles[0] : $roles;

        return $this->roleMap[$role] ?? false;
    }

    public function hasAnyRole(...$roles): bool
    {
        $flat = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $flat = array_merge($flat, $role);
            } else {
                $flat[] = $role;
            }
        }
        $key = implode(',', $flat);

        return $this->anyRoleMap[$key] ?? false;
    }
}

beforeEach(function () {
    $this->policy = new RolesPolicy;
});

test('is admin checks super admin or admin role', function () {
    $user = new UserWithRoles;
    $user->setAnyRoleResult(['super_admin', 'admin'], true);

    expect($this->policy->callIsAdmin($user))->toBeTrue();
});

test('is admin returns false for non admin', function () {
    $user = new UserWithRoles;
    $user->setAnyRoleResult(['super_admin', 'admin'], false);

    expect($this->policy->callIsAdmin($user))->toBeFalse();
});

test('is teacher checks teacher role', function () {
    $user = new UserWithRoles;
    $user->setRoleResult('teacher', true);

    expect($this->policy->callIsTeacher($user))->toBeTrue();
});

test('is student checks student role', function () {
    $user = new UserWithRoles;
    $user->setRoleResult('student', true);

    expect($this->policy->callIsStudent($user))->toBeTrue();
});

test('is supervisor checks supervisor role', function () {
    $user = new UserWithRoles;
    $user->setRoleResult('supervisor', true);

    expect($this->policy->callIsSupervisor($user))->toBeTrue();
});

test('is admin or teacher checks admin and teacher roles', function () {
    $user = new UserWithRoles;
    $user->setAnyRoleResult(['super_admin', 'admin', 'teacher'], true);

    expect($this->policy->callIsAdminOrTeacher($user))->toBeTrue();
});

test('can manage any role delegates to is admin', function () {
    $user = new UserWithRoles;
    $user->setAnyRoleResult(['super_admin', 'admin'], true);

    expect($this->policy->callCanManageAnyRole($user))->toBeTrue();
});

test('has any of roles delegates to user', function () {
    $user = new UserWithRoles;
    $user->setAnyRoleResult(['editor', 'moderator'], true);

    expect($this->policy->callHasAnyOfRoles($user, ['editor', 'moderator']))->toBeTrue();
});
