<?php

declare(strict_types=1);

describe('Fundamental Coding Standards Verification', function () {
    test('global: strict types', function () {
        expect(['App', 'Modules', 'Tests'])
            ->classes()
            ->toUseStrictTypes();
    });

    test('global: clean code invariants', function () {
        expect(['App', 'Modules'])
            ->classes()
            ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'env'])
            ->ignoring([
                'Modules\Shared\Support\Environment',
                'Modules\Exception',
                'Nwidart\Modules',
                'Modules\Setup\src\Services\EnvironmentAuditor.php',
            ]);
    });

    test('global: eloquent compatibility', function () {
        expect('Modules')
            ->classes()
            ->not->toUse(['Laravel\SerializableClosure']);
    });

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
        test('global: support utilities should be final', function () use ($supportNamespaces) {
            expect($supportNamespaces)->classes()->toBeFinal();
        });
    }
});
