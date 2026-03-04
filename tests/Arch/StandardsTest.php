<?php

declare(strict_types=1);

/**
 * Fundamental Coding Standards Verification.
 */
arch('global: strict types')
    ->expect(['App', 'Modules', 'Tests'])
    ->classes()
    ->toUseStrictTypes();

arch('global: clean code invariants')
    ->expect(['App', 'Modules'])
    ->classes()
    ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'env'])
    ->ignoring([
        'Modules\Shared\Support\Environment',
        'Modules\Exception',
        'Nwidart\Modules',
        'Modules\Setup\src\Services\EnvironmentAuditor.php'
    ]);

/**
 * Alignment with Engineering Standards:
 * Models SHOULD use standard Laravel get*Attribute and set*Attribute patterns
 * for maximum compatibility with serialization and frontend tooling.
 */
arch('global: eloquent compatibility')
    ->expect('Modules')
    ->classes()
    ->not->toUse(['Laravel\SerializableClosure']);
