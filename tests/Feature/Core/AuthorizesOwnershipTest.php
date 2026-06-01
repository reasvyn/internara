<?php

declare(strict_types=1);

use App\Domain\Core\Policies\Concerns\AuthorizesOwnership;
use App\Domain\Core\Policies\Concerns\AuthorizesRoles;
use Illuminate\Database\Eloquent\Model;

class AOPolicy
{
    use AuthorizesOwnership, AuthorizesRoles;

    public function isOwnerCall($user, $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwner($user, $model, $foreignKey);
    }

    public function isRelatedThroughCall($user, $model, string $relation, string $foreignKey = 'id'): bool
    {
        return $this->isRelatedThrough($user, $model, $relation, $foreignKey);
    }

    public function isOwnerOrAdminCall($user, $model, string $foreignKey = 'user_id'): bool
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
    it('confirms owner when user_id matches', function () {
        $policy = new AOPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->user_id = 'uid-1';

        expect($policy->isOwnerCall($user, $model))->toBeTrue();
    });

    it('rejects non-owner when user_id differs', function () {
        $policy = new AOPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->user_id = 'uid-2';

        expect($policy->isOwnerCall($user, $model))->toBeFalse();
    });

    it('uses custom foreign key', function () {
        $policy = new AOPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->author_id = 'uid-1';

        expect($policy->isOwnerCall($user, $model, 'author_id'))->toBeTrue();
    });

    it('checks ownership through relationship', function () {
        $policy = new AOPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $related = new AORelated;
        $related->id = 'uid-1';
        $model = new AOModel;
        $model->setRelation('profile', $related);

        expect($policy->isRelatedThroughCall($user, $model, 'profile'))->toBeTrue();
    });

    it('rejects when related model returns null', function () {
        $policy = new AOPolicy;
        $user = new AOUser;
        $user->id = 'uid-1';
        $model = new AOModel;
        $model->setRelation('profile', null);

        expect($policy->isRelatedThroughCall($user, $model, 'profile'))->toBeFalse();
    });
});
