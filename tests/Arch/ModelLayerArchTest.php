<?php

declare(strict_types=1);

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

arch('domain models extend BaseModel')
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend(BaseModel::class)
    ->ignoring(User::class);

arch('User extends Authenticatable, not BaseModel')
    ->expect(User::class)
    ->toExtend('Illuminate\Foundation\Auth\User');

arch('all models use HasUuids')
    ->expect('App\Models')
    ->toUseTraits([HasUuids::class]);

arch('BaseModel is abstract with UUID key type')
    ->expect(BaseModel::class)
    ->toBeAbstract()
    ->toHaveMethod('getKeyType')
    ->toHaveMethod('getIncrementing');

arch('models do not import Actions')
    ->expect('App\Models')
    ->not->toUse('App\Actions');
