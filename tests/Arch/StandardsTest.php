<?php

declare(strict_types=1);

/**
 * Fundamental Coding Standards Verification.
 */
arch('global: strict types')
    ->expect(['App', 'Modules', 'Tests'])
    ->toUseStrictTypes();

arch('global: clean code invariants')
    ->expect(['App', 'Modules'])
    ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'env'])
    ->ignoring(['Modules\Shared\Support\Environment', 'Modules\Exception', 'Nwidart\Modules']);

arch('global: php 8.4 model hooks')
    ->expect('Modules')
    ->classes()
    ->not->toUse(['get*Attribute', 'set*Attribute']);
