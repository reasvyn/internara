<?php

declare(strict_types=1);

/**
 * Domain Sovereignty and Structural Invariants Verification.
 */

$modulesPath = __DIR__.'/../../modules';
$modules = is_dir($modulesPath)
    ? array_map('basename', glob($modulesPath.'/*', GLOB_ONLYDIR))
    : [];

foreach ($modules as $module) {
    // 3.1 Model Isolation & Identity
    if ($module !== 'Shared' && is_dir("{$modulesPath}/{$module}/src/Models")) {
        arch("domain: {$module} models are private")
            ->expect("Modules\{$module}\Models")
            ->not->toBeUsedIn('Modules')
            ->ignoring("Modules\{$module}");

        arch("persistence: {$module} models use uuid v4 identity")
            ->expect("Modules\{$module}\Models")
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

    // 3.2 Service Layer Mandate (BaseService or EloquentQuery - CQRS)
    if ($module !== 'Shared' && is_dir("{$modulesPath}/{$module}/src/Services")) {
        arch("domain: {$module} services follow CQRS dualism")
            ->expect("Modules\{$module}\Services")
            ->classes()
            ->toExtend('Modules\Shared\Services\BaseService')
            ->ignoring([
                "Modules\{$module}\Services\Contracts",
                "Modules\{$module}\Services\Concerns",
            ]);

        arch("domain: {$module} respects modular sovereignty")
            ->expect("Modules\{$module}")
            ->not->toUse('Modules')
            ->ignoring([
                "Modules\{$module}",
                'Modules\Shared',
                'Modules\Core',
                'Modules\UI',
                'Modules\Exception',
                'Modules\Status',
                'Modules\Log',
                'Modules\Permission',
                'Modules\Setting',
                'Modules\Notification',
                'Modules\Media',
                'Modules\Support',
                'Nwidart\Modules',
                // Explicitly allowed cross-module anchors (Internal infrastructure)
                'Modules\User\Models\User',
                'Modules\Profile\Models\Profile',
            ]);
    }

    // 3.3 Thin Component Rule
    if (is_dir("{$modulesPath}/{$module}/src/Livewire")) {
        arch("domain: {$module} livewire components are thin")
            ->expect("Modules\{$module}\Livewire")
            ->not->toUse("Modules\{$module}\Models")
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
