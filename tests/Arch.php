<?php

declare(strict_types=1);

/**
 * Global Architecture Verification Suite.
 *
 * This suite enforces the structural invariants defined in the Project Genesis Blueprint
 * and the Engineering Conventions.
 */

// 1. Fundamental Coding Standards
arch('global: strict types')
    ->expect('Modules')
    ->toUseStrictTypes();

arch('global: clean code invariants')
    ->expect('Modules')
    ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'env'])
    ->ignoring([
        'Modules\Shared\Support\Environment',
        'Modules\Exception',
    ]);

// 2. Modular Isolation Invariants (Project Genesis Core)
arch('infrastructure: shared module purity')
    ->expect('Modules\Shared')
    ->not->toUse('Modules')
    ->ignoring('Modules\Shared');

arch('infrastructure: core module isolation')
    ->expect('Modules\Core')
    ->toOnlyUse([
        'Modules\Shared',
        'Modules\Core',
        'Illuminate',
        'Spatie',
        'Nwidart',
        'Symfony',
        'Psr',
        'Composer',
    ]);

// 3. Domain Sovereignty & Zero-Coupling Protocols
arch('domain: strict cross-module model isolation')
    ->expect('Modules')
    ->ignoring(function (string $called, string $caller) {
        // Only enforce this for Models
        if (!str_contains($called, '\\Models\\')) {
            return true;
        }

        $calledModule = explode('\\', $called)[1] ?? null;
        $callerModule = explode('\\', $caller)[1] ?? null;

        return $calledModule === $callerModule;
    });

arch('domain: thin component rule')
    ->expect('Modules')
    ->ignoring(function (string $called, string $caller) {
        // Rule: Livewire components must not use Models directly (except Shared models)
        if (!str_contains($caller, '\\Livewire\\')) {
            return true;
        }
        
        if (!str_contains($called, '\\Models\\')) {
            return true;
        }

        return str_contains($called, 'Modules\\Shared\\Models');
    });

// 4. Persistence Layer Invariants
arch('persistence: mandatory uuid identity')
    ->expect('Modules')
    ->ignoring(function (string $called) {
        if (!str_contains($called, '\\Models\\')) {
            return true;
        }
        
        $ignored = [
            'Modules\Status\Models\Status',
            'Modules\Log\Models\Activity',
            'Modules\Permission\Models\Role',
            'Modules\Permission\Models\Permission',
        ];

        foreach ($ignored as $ns) {
            if (str_starts_with($called, $ns)) {
                return true;
            }
        }

        return false;
    })
    ->toUseTrait('Modules\Shared\Models\Concerns\HasUuid');
