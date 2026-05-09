<?php

declare(strict_types=1);

arch('contracts are interfaces')
    ->expect('App\Contracts')
    ->toBeInterfaces();
