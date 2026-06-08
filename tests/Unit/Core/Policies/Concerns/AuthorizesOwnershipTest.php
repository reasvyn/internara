<?php

declare(strict_types=1);

use App\Core\Policies\Concerns\AuthorizesOwnership;
use Illuminate\Database\Eloquent\Model;

class OwnershipPolicy
{
    use AuthorizesOwnership;

    public function callIsOwner(Model $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwner($user, $model, $foreignKey);
    }

    public function callIsRelatedThrough(
        Model $user,
        Model $model,
        string $relation,
        string $foreignKey = 'id',
    ): bool {
        return $this->isRelatedThrough($user, $model, $relation, $foreignKey);
    }

    public function callIsOwnerOrAdmin(
        Model $user,
        Model $model,
        string $foreignKey = 'user_id',
    ): bool {
        return $this->isOwnerOrAdmin($user, $model, $foreignKey);
    }

    public function isAdmin($user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}

beforeEach(function () {
    $this->policy = new OwnershipPolicy;
});

test('is owner checks foreign key match', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('user_id')->andReturn(42);

    expect($this->policy->callIsOwner($user, $model))->toBeTrue();
});

test('is owner returns false for non matching foreign key', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('user_id')->andReturn(99);

    expect($this->policy->callIsOwner($user, $model))->toBeFalse();
});

test('is owner uses custom foreign key', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(7);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('author_id')->andReturn(7);

    expect($this->policy->callIsOwner($user, $model, 'author_id'))->toBeTrue();
});

test('is related through checks relation ownership', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $related = Mockery::mock(Model::class);
    $related->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('department')->andReturn($related);

    expect($this->policy->callIsRelatedThrough($user, $model, 'department'))->toBeTrue();
});

test('is related through returns false when relation is null', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('department')->andReturnNull();

    expect($this->policy->callIsRelatedThrough($user, $model, 'department'))->toBeFalse();
});

test('is owner or admin returns true when user is owner', function () {
    $user = Mockery::mock(Model::class);
    $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('user_id')->andReturn(42);

    expect($this->policy->callIsOwnerOrAdmin($user, $model))->toBeTrue();
});

test('is owner or admin returns true when user is admin', function () {
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
