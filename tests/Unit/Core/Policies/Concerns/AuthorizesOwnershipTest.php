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

class RelatedDepartment extends Model
{
    protected $attributes = ['id' => 42];

    protected $fillable = ['id'];
}

class AdminUser extends Model
{
    public function hasAnyRole(...$roles): bool
    {
        return true;
    }
}

class LimitedOwnershipPolicy
{
    use AuthorizesOwnership;

    public function callIsOwnerOrAdmin(
        Model $user,
        Model $model,
        string $foreignKey = 'user_id',
    ): bool {
        return $this->isOwnerOrAdmin($user, $model, $foreignKey);
    }
}

class TestUser extends Model {}

beforeEach(function () {
    $this->policy = new OwnershipPolicy;
});

test('is owner checks foreign key match', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 42);

    expect($this->policy->callIsOwner($user, $model))->toBeTrue();
});

test('is owner returns false for non matching foreign key', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 99);

    expect($this->policy->callIsOwner($user, $model))->toBeFalse();
});

test('is owner uses custom foreign key', function () {
    $user = new TestUser;
    $user->setAttribute('id', 7);

    $model = new TestUser;
    $model->setAttribute('author_id', 7);

    expect($this->policy->callIsOwner($user, $model, 'author_id'))->toBeTrue();
});

test('is related through checks relation ownership', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $related = new RelatedDepartment;
    $related->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('department', $related);

    expect($this->policy->callIsRelatedThrough($user, $model, 'department'))->toBeTrue();
});

test('is related through returns false when relation is null', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('department', null);

    expect($this->policy->callIsRelatedThrough($user, $model, 'department'))->toBeFalse();
});

test('is owner or admin returns true when user is owner', function () {
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 42);

    expect($this->policy->callIsOwnerOrAdmin($user, $model))->toBeTrue();
});

test('is owner or admin returns true when user is admin', function () {
    $user = new AdminUser;
    $user->setAttribute('id', 42);

    $model = new TestUser;
    $model->setAttribute('user_id', 99);

    expect($this->policy->callIsOwnerOrAdmin($user, $model))->toBeTrue();
});

test('is owner or admin falls back to ownership check without isAdmin method', function () {
    $policy = new LimitedOwnershipPolicy;
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $ownerModel = new TestUser;
    $ownerModel->setAttribute('user_id', 42);

    expect($policy->callIsOwnerOrAdmin($user, $ownerModel))->toBeTrue();
});

test('is owner or admin returns false without isAdmin when not owner', function () {
    $policy = new LimitedOwnershipPolicy;
    $user = new TestUser;
    $user->setAttribute('id', 42);

    $otherModel = new TestUser;
    $otherModel->setAttribute('user_id', 99);

    expect($policy->callIsOwnerOrAdmin($user, $otherModel))->toBeFalse();
});
