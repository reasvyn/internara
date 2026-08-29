<?php

declare(strict_types=1);

use App\Modules\Core\Support\ModuleManager;

test('B114U-FR-MG1: names() returns the registered module list', function () {
    config()->set('module.list', ['Core', 'Setup']);

    expect(ModuleManager::names())->toBe(['Core', 'Setup']);
});

test('B114U-FR-MG2: isModule() is a strict membership check', function () {
    config()->set('module.list', ['Core', 'Setup']);

    expect(ModuleManager::isModule('Core'))->toBeTrue();
    expect(ModuleManager::isModule('core'))->toBeFalse();
    expect(ModuleManager::isModule('Nope'))->toBeFalse();
});

test('B114U-FR-MG3: registry() returns the module to submodule mapping', function () {
    config()->set('module.registry', ['Enrollment' => ['Wizard', 'Placement']]);

    expect(ModuleManager::registry()['Enrollment'])->toBe(['Wizard', 'Placement']);
});

test('B114U-FR-MG4: submodules() returns an empty list for unknown modules', function () {
    config()->set('module.registry', []);

    expect(ModuleManager::submodules('Unknown'))->toBe([]);
});

test('B114U-FR-MG5: testDirs() returns the non-module test directories', function () {
    config()->set('module.test_dirs', ['Common']);

    expect(ModuleManager::testDirs())->toBe(['Common']);
});

test('B114U-FR-MG6: path accessors return the configured module paths', function () {
    config()->set('module.paths', [
        'base' => '/code/app',
        'views' => '/code/resources/views',
        'routes' => '/code/routes/web',
    ]);

    expect(ModuleManager::basePath())->toBe('/code/app');
    expect(ModuleManager::viewsPath())->toBe('/code/resources/views');
    expect(ModuleManager::routesPath())->toBe('/code/routes/web');
});

test('B114U-FR-MG7: feature flags are typed boolean accessors', function () {
    config()->set('module.policies.enabled', false);
    config()->set('module.livewire.enabled', true);
    config()->set('module.views.enabled', true);
    config()->set('module.factories.enabled', false);

    expect(ModuleManager::policiesEnabled())->toBeFalse();
    expect(ModuleManager::livewireEnabled())->toBeTrue();
    expect(ModuleManager::viewsEnabled())->toBeTrue();
    expect(ModuleManager::factoriesEnabled())->toBeFalse();
});

test('B114U-FR-MG8: Livewire directory and exclude paths are configurable', function () {
    config()->set('module.livewire.directory', 'Livewire');
    config()->set('module.livewire.exclude_paths', ['Concerns']);

    expect(ModuleManager::livewireDirectory())->toBe('Livewire');
    expect(ModuleManager::livewireExcludePaths())->toBe(['Concerns']);
});

test('B114U-FR-MG9: policy directory, exclude paths and model namespace are configurable', function () {
    config()->set('module.policies.directory', 'Policies');
    config()->set('module.policies.exclude_paths', ['Traits']);
    config()->set('module.policies.model_namespace', 'App\\{domain}\\Models\\{model}');

    expect(ModuleManager::policiesDirectory())->toBe('Policies');
    expect(ModuleManager::policiesExcludePaths())->toBe(['Traits']);
    expect(ModuleManager::policyModelNamespace())->toBe('App\\{domain}\\Models\\{model}');
});

test('B114U-FR-MG10: viewsExcludeDirectories() returns the default excluded view directories', function () {
    $excluded = ModuleManager::viewsExcludeDirectories();

    expect($excluded)->toContain('components');
    expect($excluded)->toContain('emails');
    expect($excluded)->toContain('layouts');
});

test('B114U-FR-MG11: routeFilePath() lowercases the module name', function () {
    config()->set('module.paths.routes', '/code/routes/web');

    expect(ModuleManager::routeFilePath('Enrollment'))->toBe('/code/routes/web/enrollment.php');
});

test('B114U-FR-MG12: isRegisteredDirectory() compares module names case-insensitively', function () {
    config()->set('module.list', ['Core', 'Setup']);

    expect(ModuleManager::isRegisteredDirectory('enrollment'))->toBeFalse();
    config()->set('module.list', ['Enrollment']);

    expect(ModuleManager::isRegisteredDirectory('enrollment'))->toBeTrue();
    expect(ModuleManager::isRegisteredDirectory('Enrollment'))->toBeTrue();
});

test('B114U-FR-MG14: ModuleManager is a static-only gateway with no constructor', function () {
    expect((new ReflectionClass(ModuleManager::class))->getConstructor())->toBeNull();
    expect((new ReflectionMethod(ModuleManager::class, 'names'))->isStatic())->toBeTrue();
});
