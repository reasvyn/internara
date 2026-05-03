<?php

declare(strict_types=1);

namespace Tests\Arch;

/**
 * S1 - Secure & S2 - Sustain: Global Coding Standards
 * Ensures all code in App namespace follows basic standards
 */
describe('Global Coding Standards', function () {
    test('global: strict types')->expect('App')->toUseStrictTypes();

    test('global: clean code invariants')
        ->expect('App')
        ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'env'])
        ->ignoring([
            // Add specific exceptions here if needed
        ]);

    test('global: no hardcoded secrets in code')
        ->expect('App')
        ->not->toUse(['API_KEY', 'SECRET_KEY', 'password =', 'secret ='])
        ->ignoring([
            // Allow password field references in Models
            'App\Domain\User\Models\User',
        ]);
});
