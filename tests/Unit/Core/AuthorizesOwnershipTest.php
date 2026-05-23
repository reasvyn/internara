<?php

declare(strict_types=1);

use App\Domain\Core\Policies\Concerns\AuthorizesOwnership;
use App\Domain\Core\Policies\Concerns\AuthorizesRoles;
use Illuminate\Database\Eloquent\Model;

class AOTestPolicy
{
    use AuthorizesOwnership, AuthorizesRoles;

    public function callIsOwner($user, $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwner($user, $model, $foreignKey);
    }

    public function callIsRelatedThrough($user, $model, string $relation, string $foreignKey = 'id'): bool
    {
        return $this->isRelatedThrough($user, $model, $relation, $foreignKey);
    }

    public function callIsOwnerOrAdmin($user, $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwnerOrAdmin($user, $model, $foreignKey);
    }
}

class AOOwnerOnlyPolicy
{
    use AuthorizesOwnership;

    public function callIsOwnerOrAdmin($user, $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwnerOrAdmin($user, $model, $foreignKey);
    }
}

class AOUser extends Model
{
    protected $table = 'ao_users';

    public $incrementing = false;

    protected $keyType = 'string';
}

class AOModel extends Model
{
    protected $table = 'ao_models';
}

class AORelated extends Model
{
    protected $table = 'ao_related';

    public $incrementing = false;

    protected $keyType = 'string';
}

describe('AuthorizesOwnership', function () {
    it('checks owner via user_id', function () {
        $policy = new AOTestPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->user_id = 'uid-1';

        expect($policy->callIsOwner($user, $model))->toBeTrue();
    });

    it('rejects non-owner via user_id', function () {
        $policy = new AOTestPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->user_id = 'uid-2';

        expect($policy->callIsOwner($user, $model))->toBeFalse();
    });

    it('checks owner with custom foreign key', function () {
        $policy = new AOTestPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->author_id = 'uid-1';

        expect($policy->callIsOwner($user, $model, 'author_id'))->toBeTrue();
    });

    it('checks related through relationship', function () {
        $policy = new AOTestPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $related = new AORelated;
        $related->id = 'uid-1';

        $model = new AOModel;
        $model->setRelation('profile', $related);

        expect($policy->callIsRelatedThrough($user, $model, 'profile'))->toBeTrue();
    });

    it('rejects related through when relation returns null', function () {
        $policy = new AOTestPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';

        $model = new AOModel;
        $model->setRelation('profile', null);

        expect($policy->callIsRelatedThrough($user, $model, 'profile'))->toBeFalse();
    });

    it('isOwnerOrAdmin returns true for owner', function () {
        $policy = new AOTestPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->user_id = 'uid-1';

        expect($policy->callIsOwnerOrAdmin($user, $model))->toBeTrue();
    });

    it('isOwnerOrAdmin returns false for non-owner when no admin trait', function () {
        $policy = new AOOwnerOnlyPolicy;
        $user = new AOUser;
        $user->id = 'uid-a';
        $model = new AOModel;
        $model->user_id = 'uid-b';

        expect($policy->callIsOwnerOrAdmin($user, $model))->toBeFalse();
    });
});
