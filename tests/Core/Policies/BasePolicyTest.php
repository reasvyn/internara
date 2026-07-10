<?php

declare(strict_types=1);

namespace Tests\Core\Policies;

use App\Core\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    public function hasAnyRole(...$roles): bool
    {
        return true;
    }
}

class NonAdminUser extends Model
{
    public function hasAnyRole(...$roles): bool
    {
        return false;
    }
}

class TestUser extends Model {}

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
    $user = new AdminUser;

    expect($this->policy->callIsAdmin($user))->toBeTrue();
});

test('base policy is admin returns false for non-admin user', function () {
    $user = new NonAdminUser;

    expect($this->policy->callIsAdmin($user))->toBeFalse();
});

test('base policy is owner checks foreign key match', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 42);

    expect($this->policy->callIsOwner($user, $model))->toBeTrue();
});

test('base policy is owner returns false for non matching foreign key', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 99);

    expect($this->policy->callIsOwner($user, $model))->toBeFalse();
});

test('base policy is owner or admin returns true when user is admin', function () {
    $user = new AdminUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 99);

    expect($this->policy->callIsOwnerOrAdmin($user, $model))->toBeTrue();
});

test('base policy is owner or admin returns true when user is owner', function () {
    $user = new NonAdminUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 42);

    expect($this->policy->callIsOwnerOrAdmin($user, $model))->toBeTrue();
});

test('base policy is owner or admin returns false for non-owner non-admin', function () {
    $user = new NonAdminUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 99);

    expect($this->policy->callIsOwnerOrAdmin($user, $model))->toBeFalse();
});

test('base policy is related through checks relation ownership', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $related = new TestUser;
    $related->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('department', $related);

    expect($this->policy->callIsRelatedThrough($user, $model, 'department'))->toBeTrue();
});

test('base policy is related through returns false when relation is null', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('department', null);

    expect($this->policy->callIsRelatedThrough($user, $model, 'department'))->toBeFalse();
});
