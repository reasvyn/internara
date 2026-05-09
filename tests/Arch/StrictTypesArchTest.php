<?php

declare(strict_types=1);

arch('app uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debug functions in app')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die']);
