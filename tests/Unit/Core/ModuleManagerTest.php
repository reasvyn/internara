<?php

declare(strict_types=1);

use App\Modules\Core\Support\ModuleManager;

describe('B114U: ModuleManager gateway', function (): void {
    test('B114U-FR-MGR-001: names returns the registered module list', function (): void {
        config(['module.list' => ['Academics', 'Auth']]);

        expect(ModuleManager::names())->toBe(['Academics', 'Auth']);
    });

    test('B114U-FR-MGR-002: isModule checks strict membership', function (): void {
        config(['module.list' => ['Academics']]);

        expect(ModuleManager::isModule('Academics'))->toBeTrue();
        expect(ModuleManager::isModule('academics'))->toBeFalse();
        expect(ModuleManager::isModule('Unknown'))->toBeFalse();
    });

    test('B114U-FR-MGR-003: registry returns the module to domain mapping', function (): void {
        config(['module.registry' => ['Academics' => ['AcademicYear', 'Department']]]);

        expect(ModuleManager::registry())->toBe(['Academics' => ['AcademicYear', 'Department']]);
    });

    test('B114U-FR-MGR-004: domains resolves the domain list and submodules aliases it', function (): void {
        config(['module.registry' => ['Academics' => ['AcademicYear']]]);

        expect(ModuleManager::domains('Academics'))->toBe(['AcademicYear']);
        expect(ModuleManager::domains('Unknown'))->toBe([]);
        expect(ModuleManager::submodules('Academics'))->toBe(['AcademicYear']);
    });

    test('B114U-FR-MGR-005: testDirs returns the non-module test directories', function (): void {
        config(['module.test_dirs' => ['Support']]);

        expect(ModuleManager::testDirs())->toBe(['Support']);
    });

    test('B114U-FR-MGR-006: basePath, viewsPath, and routesPath return configured paths', function (): void {
        config([
            'module.paths.base' => '/app/Modules',
            'module.paths.views' => '/resources/views',
            'module.paths.routes' => '/routes/web',
        ]);

        expect(ModuleManager::basePath())->toBe('/app/Modules');
        expect(ModuleManager::viewsPath())->toBe('/resources/views');
        expect(ModuleManager::routesPath())->toBe('/routes/web');
    });

    test('B114U-FR-MGR-007: feature flags expose typed boolean accessors', function (): void {
        config([
            'module.policies.enabled' => false,
            'module.livewire.enabled' => true,
            'module.views.enabled' => false,
            'module.factories.enabled' => true,
        ]);

        expect(ModuleManager::policiesEnabled())->toBeFalse();
        expect(ModuleManager::livewireEnabled())->toBeTrue();
        expect(ModuleManager::viewsEnabled())->toBeFalse();
        expect(ModuleManager::factoriesEnabled())->toBeTrue();
    });

    test('B114U-FR-MGR-008: livewire discovery settings round-trip through config', function (): void {
        config(['module.livewire.directory' => 'Livewire', 'module.livewire.exclude_paths' => ['Concerns']]);

        expect(ModuleManager::livewireDirectory())->toBe('Livewire');
        expect(ModuleManager::livewireExcludePaths())->toBe(['Concerns']);
    });

    test('B114U-FR-MGR-009: policy discovery settings round-trip through config', function (): void {
        config([
            'module.policies.directory' => 'Policies',
            'module.policies.exclude_paths' => ['Traits'],
            'module.policies.model_namespace' => 'App\\{domain}\\Models\\{model}',
        ]);

        expect(ModuleManager::policiesDirectory())->toBe('Policies');
        expect(ModuleManager::policiesExcludePaths())->toBe(['Traits']);
        expect(ModuleManager::policyModelNamespace())->toBe('App\\{domain}\\Models\\{model}');
    });

    test('B114U-FR-MGR-010: viewsExcludeDirectories returns the namespace exclusions', function (): void {
        config(['module.views.exclude_directories' => ['layouts', 'pdf']]);

        expect(ModuleManager::viewsExcludeDirectories())->toBe(['layouts', 'pdf']);
    });

    test('B114U-FR-MGR-011: routeFilePath lowercases the module name', function (): void {
        config(['module.paths.routes' => '/routes/web']);

        expect(ModuleManager::routeFilePath('Academics'))->toBe('/routes/web/academics.php');
        expect(ModuleManager::routeFilePath('User'))->toBe('/routes/web/user.php');
    });

    test('B114U-FR-MGR-012: isRegisteredDirectory compares case-insensitively', function (): void {
        config(['module.list' => ['Academics', 'User']]);

        expect(ModuleManager::isRegisteredDirectory('academics'))->toBeTrue();
        expect(ModuleManager::isRegisteredDirectory('USER'))->toBeTrue();
        expect(ModuleManager::isRegisteredDirectory('unknown'))->toBeFalse();
    });

    test('B114U-FR-MGR-014: the gateway instantiates with only static behavior', function (): void {
        $manager = new ModuleManager;

        expect($manager)->toBeInstanceOf(ModuleManager::class);
    });
});
