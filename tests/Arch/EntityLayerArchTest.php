<?php

declare(strict_types=1);

use App\Domain\Core\Entities\BaseEntity;

arch('BaseEntity is abstract readonly with fromModel')
    ->expect(BaseEntity::class)
    ->toBeAbstract()
    ->toBeReadonly()
    ->toHaveMethod('fromModel');
