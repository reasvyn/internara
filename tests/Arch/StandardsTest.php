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
        'Modules\Setup\src\Services\EnvironmentAuditor.php',
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

$modulesPath = __DIR__ . '/../../modules';
$modulesStatuses =
    json_decode(file_get_contents(__DIR__ . '/../../modules_statuses.json'), true) ?? [];
$modules = array_keys(array_filter($modulesStatuses, fn($status) => $status === true));

$supportNamespaces = [];
foreach ($modules as $module) {
    if (is_dir("{$modulesPath}/{$module}/src/Support")) {
        $supportNamespaces[] = "Modules\\{$module}\\Support";
    }
}

if (!empty($supportNamespaces)) {
    arch('global: support utilities should be final')
        ->expect($supportNamespaces)
        ->classes()
        ->toBeFinal();
}
