<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Policies;

use App\Core\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class MockPolicy extends BasePolicy
{
    public function callIsAdmin(Model $user): bool
    {
        return $this->isAdmin($user);
    }

    public function callIsOwner(Model $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwner($user, $model, $foreignKey);
    }

    public function callIsOwnerOrAdmin(
        Model $user,
        Model $model,
        string $foreignKey = 'user_id',
    ): bool {
        return $this->isOwnerOrAdmin($user, $model, $foreignKey);
    }

    public function callIsRelatedThrough(
        Model $user,
        Model $model,
        string $relation,
        string $foreignKey = 'id',
    ): bool {
        return $this->isRelatedThrough($user, $model, $relation, $foreignKey);
    }
}

beforeEach(function () {
    $this->policy = new MockPolicy;
});

test('base policy imports authorizes ownership and roles traits', function () {
    expect(method_exists($this->policy, 'isAdmin'))->toBeTrue();
    expect(method_exists($this->policy, 'isOwner'))->toBeTrue();
});

test('base policy is admin delegates to user has any role', function () {
    $user = Mockery::mock(Model::class);
    $user
        ->shouldReceive('hasAnyRole')
        ->with(['super_admin', 'admin'])
        ->once()
        ->andReturnTrue();

    expect($this->policy->callIsAdmin($user))->toBeTrue();
});

test('base policy is owner checks foreign key match', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('user_id')->andReturn(42);

    expect($this->policy->callIsOwner($user, $model))->toBeTrue();
});

test('base policy is owner or admin returns true when user is admin', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);
    $user
        ->shouldReceive('hasAnyRole')
        ->with(['super_admin', 'admin'])
        ->once()
        ->andReturnTrue();

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('user_id')->andReturn(99);

    expect($this->policy->callIsOwnerOrAdmin($user, $model))->toBeTrue();
});

test('base policy is related through checks relation ownership', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $related = Mockery::mock(Model::class);
    $related->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('department')->andReturn($related);

    expect($this->policy->callIsRelatedThrough($user, $model, 'department'))->toBeTrue();
});
