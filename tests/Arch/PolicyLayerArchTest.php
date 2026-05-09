<?php

declare(strict_types=1);

use App\Policies\Shared\BasePolicy;

arch('domain policies extend BasePolicy')
    ->expect('App\Policies')
    ->classes()
    ->toExtend(BasePolicy::class);

arch('shared policy concerns are traits')
    ->expect('App\Policies\Shared\Concerns')
    ->toBeTraits();
