<?php

declare(strict_types=1);

/**
 * Global Architecture Verification Suite.
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
    ])
    ->ignoring(['app', 'config', 'setting', 'url', 'redirect', 'request', 'session', 'trans', '__', 'view', 'Symfony\Component\HttpFoundation\Response']);

// 3. Domain Sovereignty & Zero-Coupling Protocols
$modulesPath = __DIR__ . '/../modules';
$modules = is_dir($modulesPath) ? array_map('basename', glob($modulesPath . '/*', GLOB_ONLYDIR)) : [];

foreach ($modules as $module) {
    // Model Isolation
    if ($module !== 'Shared' && is_dir("{$modulesPath}/{$module}/src/Models")) {
        arch("domain: {$module} models are isolated")
            ->expect("Modules\\{$module}\\Models")
            ->not->toBeUsedIn('Modules')
            ->ignoring("Modules\\{$module}");
            
        arch("persistence: {$module} models use uuid")
            ->expect("Modules\\{$module}\\Models")
            ->classes()
            ->toUseTrait('Modules\Shared\Models\Concerns\HasUuid')
            ->ignoring([
                'Modules\Status\Models\Status',
                'Modules\Log\Models\Activity',
                'Modules\Permission\Models\Role',
                'Modules\Permission\Models\Permission',
                'Modules\Media\Models\Media',
                'Modules\Setting\Models\Setting', // Setting module uses string keys by design
            ]);
    }

    // Thin Component Rule
    if (is_dir("{$modulesPath}/{$module}/src/Livewire") || is_dir("{$modulesPath}/{$module}/resources/views/livewire")) {
        arch("domain: {$module} livewire components are thin")
            ->expect("Modules\\{$module}\\Livewire")
            ->not->toUse("Modules\\{$module}\\Models")
            ->ignoring('Modules\Shared\Models');
    }
}
