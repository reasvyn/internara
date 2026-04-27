<?php

declare(strict_types=1);

/**
 * Domain Sovereignty and Structural Invariants Verification.
 */
$modulesPath = __DIR__ . '/../../modules';
$modulesStatuses =
    json_decode(file_get_contents(__DIR__ . '/../../modules_statuses.json'), true) ?? [];
$modules = array_keys(array_filter($modulesStatuses, fn($status) => $status === true));

foreach ($modules as $module) {
    describe("{$module} Module", function () use ($module, $modulesPath) {
        // 3.1 Model Persistence
        if ($module !== 'Shared' && is_dir("{$modulesPath}/{$module}/src/Models")) {
            test("persistence: {$module} models use uuid v4 identity")
                ->expect("Modules\\{$module}\\Models")
                ->classes()
                ->toUseTrait('Modules\\Shared\\Models\\Concerns\\HasUuid')
                ->ignoring([
                    'Modules\\Status\\Models\\Status',
                    'Modules\\Log\\Models\\Activity',
                    'Modules\\Permission\\Models\\Role',
                    'Modules\\Permission\\Models\\Permission',
                    'Modules\\Media\\Models\\Media',
                    'Modules\\Setting\\Models\\Setting',
                    'Modules\\Profile\\Models\\Profile',
                ]);
        }

        // 3.2 Service Layer Mandate
        if ($module !== 'Shared' && is_dir("{$modulesPath}/{$module}/src/Services")) {
            test("domain: {$module} services extend authoritative base classes")
                ->expect("Modules\\{$module}\\Services")
                ->classes()
                ->toExtend('Modules\\Shared\\Services\\BaseService')
                ->ignoring([
                    "Modules\\{$module}\\Services\\Contracts",
                    "Modules\\{$module}\\Services\\Concerns",
                ]);

            test("domain: {$module} respects modular sovereignty")
                ->expect("Modules\\{$module}")
                ->classes()
                ->not->toUse('Modules')
                ->ignoring([
                    "Modules\\{$module}",
                    'Modules\\Shared',
                    'Modules\\Core',
                    'Modules\\UI',
                    'Modules\\Exception',
                    'Modules\\Status',
                    'Modules\\Log',
                    'Modules\\Permission',
                    'Modules\\Setting',
                    'Modules\\Notification',
                    'Modules\\Media',
                    'Modules\\Support',
                    'Nwidart\\Modules',
                    'Modules\\User\\Models\\User',
                    'Modules\\Profile\\Models\\Profile',
                ]);
        }

        // 3.4 Namespace Omission Rule
        test("standards: {$module} omits src from namespace")
            ->expect("Modules\\{$module}")
            ->classes()
            ->not->toUse("Modules\\{$module}\\src");

        // 3.5 Third-Party Wrapper Mandate
        test("Dependency: {$module} has not used third-party directly")
            ->expect("Modules\\{$module}")
            ->classes()
            ->not->toUse(['Spatie', 'Livewire\Volt', 'Barryvdh', 'Mhmiton'])
            ->ignoring([
                'Modules\\Shared',
                'Modules\\Permission',
                'Modules\\Status',
                'Modules\\Log',
                'Modules\\Media',
                'Modules\\User',
            ]);
    });
}
