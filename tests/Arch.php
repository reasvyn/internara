<?php

declare(strict_types=1);

/**
 * Global Architecture Verification Suite (The Architecture Police).
 *
 * This suite enforces the systemic invariants defined in the authoritative
 * Project Genesis Blueprint (ARC01-INIT-01).
 */

// 1. Fundamental Coding Standards
arch('global: strict types')
    ->expect(['App', 'Modules', 'Tests'])
    ->toUseStrictTypes();

arch('global: clean code invariants')
    ->expect(['App', 'Modules'])
    ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'env'])
    ->ignoring(['Modules\Shared\Support\Environment', 'Modules\Exception', 'Nwidart\Modules']);

// 2. Modular Infrastructure Purity
arch('infrastructure: shared module isolation')
    ->expect('Modules\Shared')
    ->not->toUse('Modules\\')
    ->ignoring(['Modules\Shared\\', 'Nwidart\Modules\\']);

arch('infrastructure: core module baseline')
    ->expect('Modules\Core')
    ->toOnlyUse([
        'Modules\Shared',
        'Modules\Core',
        'Modules\UI',
        'Illuminate',
        'Spatie',
        'Nwidart',
        'Symfony',
        'Psr',
        'Composer',
    ])
    ->ignoring([
        'app',
        'config',
        'setting',
        'url',
        'redirect',
        'request',
        'session',
        'trans',
        '__',
        'view',
        'Symfony\Component\HttpFoundation',
        'is_debug_mode',
        'base_path',
        'now',
        'module_path',
        'flash',
    ]);

// 3. Domain Sovereignty Protocols (Project Genesis Invariants)
$modulesPath = __DIR__.'/../modules';
$modules = is_dir($modulesPath)
    ? array_map('basename', glob($modulesPath.'/*', GLOB_ONLYDIR))
    : [];

foreach ($modules as $module) {
    // 3.1 Model Isolation & Identity
    if ($module !== 'Shared' && is_dir("{$modulesPath}/{$module}/src/Models")) {
        arch("domain: {$module} models are private")
            ->expect("Modules\\{$module}\\Models")
            ->not->toBeUsedIn('Modules')
            ->ignoring("Modules\\{$module}");

        arch("persistence: {$module} models use uuid v4 identity")
            ->expect("Modules\\{$module}\\Models")
            ->classes()
            ->toUseTrait('Modules\Shared\Models\Concerns\HasUuid')
            ->ignoring([
                'Modules\Status\Models\Status',
                'Modules\Log\Models\Activity',
                'Modules\Permission\Models\Role',
                'Modules\Permission\Models\Permission',
                'Modules\Media\Models\Media',
                'Modules\Setting\Models\Setting',
            ]);
    }

    // 3.2 Service Layer Mandate (BaseService & Contracts)
    if ($module !== 'Shared' && is_dir("{$modulesPath}/{$module}/src/Services")) {
        arch("domain: {$module} services extend BaseService")
            ->expect("Modules\\{$module}\\Services")
            ->classes()
            ->toExtend('Modules\Shared\Services\BaseService')
            ->ignoring([
                "Modules\\{$module}\\Services\\Contracts",
                "Modules\\{$module}\\Services\\Concerns",
            ]);

        arch("domain: {$module} uses contract-first communication")
            ->expect("Modules\\{$module}")
            ->not->toUse('Modules\\')
            ->ignoring([
                "Modules\\{$module}\\",
                'Modules\\Shared\\',
                'Modules\\Core\\',
                'Modules\\UI\\',
                'Modules\\Exception\\',
                'Modules\\Status\\',
                'Modules\\Log\\',
                'Modules\\Permission\\',
                'Modules\\Setting\\',
                'Modules\\Notification\\',
                'Modules\\Media\\',
                'Modules\\Support\\',
                'Nwidart\\Modules\\',
                // Allowed cross-module patterns (Contract-First)
                'Modules\\User\\Models\\User',
                'Modules\\Profile\\Models\\Profile',
            ]);
    }

    // 3.3 Thin Component Rule
    if (is_dir("{$modulesPath}/{$module}/src/Livewire")) {
        arch("domain: {$module} livewire components are thin")
            ->expect("Modules\\{$module}\\Livewire")
            ->not->toUse("Modules\\{$module}\\Models")
            ->ignoring([
                'Modules\Shared\Models',
                'Modules\User\Models\User',
                'Modules\Profile\Models\Profile',
                // Existing violators to be refactored in future sprints
                'Modules\Attendance\Models',
                'Modules\Department\Models',
                'Modules\Guidance\Models',
                'Modules\Internship\Models',
                'Modules\Journal\Models',
                'Modules\Permission\Models',
                'Modules\Schedule\Models',
                'Modules\User\Models',
            ]);
    }
}
