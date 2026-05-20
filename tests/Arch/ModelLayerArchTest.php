<?php

declare(strict_types=1);

use App\Domain\Core\Models\BaseModel;
use App\Domain\User\Models\User;

arch('BaseModel is abstract with UUID key methods')
    ->expect(BaseModel::class)
    ->toBeAbstract()
    ->toHaveMethod('getIncrementing')
    ->toHaveMethod('getKeyType');

arch('User extends Authenticatable, not BaseModel')
    ->expect(User::class)
    ->toExtend('Illuminate\Foundation\Auth\User');
