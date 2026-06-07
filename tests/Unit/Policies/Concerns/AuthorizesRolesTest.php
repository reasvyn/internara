<?php

declare(strict_types=1);

use App\Policies\Concerns\AuthorizesRoles;
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

beforeEach(function () {
    $this->policy = new RolesPolicy;
});

test('is admin checks super admin or admin role', function () {
    $user = Mockery::mock(Model::class);
    $user
        ->shouldReceive('hasAnyRole')
        ->with(['super_admin', 'admin'])
        ->once()
        ->andReturnTrue();

    expect($this->policy->callIsAdmin($user))->toBeTrue();
});

test('is admin returns false for non admin', function () {
    $user = Mockery::mock(Model::class);
    $user
        ->shouldReceive('hasAnyRole')
        ->with(['super_admin', 'admin'])
        ->once()
        ->andReturnFalse();

    expect($this->policy->callIsAdmin($user))->toBeFalse();
});

test('is teacher checks teacher role', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('hasRole')->with('teacher')->once()->andReturnTrue();

    expect($this->policy->callIsTeacher($user))->toBeTrue();
});

test('is student checks student role', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('hasRole')->with('student')->once()->andReturnTrue();

    expect($this->policy->callIsStudent($user))->toBeTrue();
});

test('is supervisor checks supervisor role', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('hasRole')->with('supervisor')->once()->andReturnTrue();

    expect($this->policy->callIsSupervisor($user))->toBeTrue();
});

test('is admin or teacher checks admin and teacher roles', function () {
    $user = Mockery::mock(Model::class);
    $user
        ->shouldReceive('hasAnyRole')
        ->with(['super_admin', 'admin', 'teacher'])
        ->once()
        ->andReturnTrue();

    expect($this->policy->callIsAdminOrTeacher($user))->toBeTrue();
});

test('can manage any role delegates to is admin', function () {
    $user = Mockery::mock(Model::class);
    $user
        ->shouldReceive('hasAnyRole')
        ->with(['super_admin', 'admin'])
        ->once()
        ->andReturnTrue();

    expect($this->policy->callCanManageAnyRole($user))->toBeTrue();
});

test('has any of roles delegates to user', function () {
    $user = Mockery::mock(Model::class);
    $user
        ->shouldReceive('hasAnyRole')
        ->with(['editor', 'moderator'])
        ->once()
        ->andReturnTrue();

    expect($this->policy->callHasAnyOfRoles($user, ['editor', 'moderator']))->toBeTrue();
});
