<?php

declare(strict_types=1);

use App\Entities\BaseEntity;

arch('entities extend BaseEntity')
    ->expect('App\Entities')
    ->classes()
    ->ignoring(BaseEntity::class)
    ->toExtend(BaseEntity::class);

arch('entities are final readonly')
    ->expect('App\Entities')
    ->classes()
    ->ignoring(BaseEntity::class)
    ->toBeFinal()
    ->toBeReadonly();

arch('BaseEntity is abstract readonly with fromModel')
    ->expect(BaseEntity::class)
    ->toBeAbstract()
    ->toBeReadonly()
    ->toHaveMethod('fromModel');

arch('entities do not import App\Models, only BaseEntity does')
    ->expect('App\Entities')
    ->not->toUse('App\Models')
    ->ignoring('App\Entities\User');
