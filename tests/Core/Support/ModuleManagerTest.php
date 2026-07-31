<?php

declare(strict_types=1);

use App\Core\Support\ModuleManager;

test('returns registered module names from config', function () {
    expect(ModuleManager::names())->toContain('Core', 'Enrollment', 'Auth')
        ->and(ModuleManager::names())->toBeList();
});

test('isModule performs strict membership check', function () {
    expect(ModuleManager::isModule('Core'))->toBeTrue()
        ->and(ModuleManager::isModule('NotAModule'))->toBeFalse();
});

test('returns registry mapping and submodules', function () {
    expect(ModuleManager::registry())->toBeArray()
        ->and(ModuleManager::submodules('Core'))->toContain('Console')
        ->and(ModuleManager::submodules('UnknownModule'))->toBeEmpty();
});

test('returns test directories', function () {
    expect(ModuleManager::testDirs())->toContain('Providers', 'Stubs', 'Support');
});

test('returns configured paths', function () {
    expect(ModuleManager::basePath())->toBe(app_path())
        ->and(ModuleManager::viewsPath())->toBe(resource_path('views'))
        ->and(ModuleManager::routesPath())->toBe(base_path('routes/web'));
});

test('returns discovery enabled flags', function () {
    expect(ModuleManager::policiesEnabled())->toBeBool()
        ->and(ModuleManager::livewireEnabled())->toBeBool()
        ->and(ModuleManager::viewsEnabled())->toBeBool()
        ->and(ModuleManager::factoriesEnabled())->toBeBool();
});

test('returns discovery settings', function () {
    expect(ModuleManager::livewireDirectory())->toBe('Livewire')
        ->and(ModuleManager::policiesDirectory())->toBe('Policies')
        ->and(ModuleManager::livewireExcludePaths())->toContain('Concerns', 'Traits')
        ->and(ModuleManager::policiesExcludePaths())->toContain('Concerns', 'Traits')
        ->and(ModuleManager::viewsExcludeDirectories())->toContain('layouts', 'components');
});

test('routeFilePath uses lowercase module convention', function () {
    expect(ModuleManager::routeFilePath('Enrollment'))->toBe(base_path('routes/web/enrollment.php'));
});

test('isRegisteredDirectory compares case-insensitively', function () {
    expect(ModuleManager::isRegisteredDirectory('core'))->toBeTrue()
        ->and(ModuleManager::isRegisteredDirectory('Core'))->toBeTrue()
        ->and(ModuleManager::isRegisteredDirectory('not-a-module'))->toBeFalse();
});
