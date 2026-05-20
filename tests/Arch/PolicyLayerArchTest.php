<?php

declare(strict_types=1);

use App\Domain\Core\Policies\BasePolicy;

arch('BasePolicy is abstract')
    ->expect(BasePolicy::class)
    ->toBeAbstract();

arch('BasePolicy uses AuthorizesRoles and AuthorizesOwnership')
    ->expect(BasePolicy::class)
    ->toUse('App\Domain\Core\Policies\Concerns\AuthorizesRoles')
    ->toUse('App\Domain\Core\Policies\Concerns\AuthorizesOwnership');
