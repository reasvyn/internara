<?php

declare(strict_types=1);
use App\Modules\Core\Support\ModuleManager;

describe('I1BCV: module discovery registry', function (): void {
    test('I1BCV-FR-MOD-003: registry is filesystem-discovered and exposes the module list', function (): void {
        $source = file_get_contents(base_path('config/module.php'));

        expect($source)->toContain('scandir')
            ->and($source)->toContain("'list'");

        $modules = config('module.list');

        expect($modules)->toBeArray()
            ->and($modules)->toContain('Core')
            ->and($modules)->toContain('Auth');
    });

    test('I1BCV-FR-MOD-038: Pest registers test directories for all modules', function (): void {
        $source = file_get_contents(base_path('tests/Pest.php'));

        expect($source)->toContain('scandir')
            ->and($source)->toContain("['Arch', 'Unit', 'Feature', 'Browser']")
            ->and($source)->toContain('->in(...$dirs)');
    });

    test('I1BCV-FR-MOD-039: Pest module list stays in sync with config/module.php', function (): void {
        $pestSource = file_get_contents(base_path('tests/Pest.php'));
        $moduleSource = file_get_contents(base_path('config/module.php'));

        expect($pestSource)->toContain('scandir')
            ->and($moduleSource)->toContain('scandir')
            ->and($moduleSource)->toContain("app_path('Modules')");
    });

    test('I1BCV-FR-MOD-041: config() is never used in tests/Pest.php', function (): void {
        $source = file_get_contents(base_path('tests/Pest.php'));

        expect($source)->not->toContain('config(');
    });

    test('I1BCV-FR-MOD-001/004/005/006/007/008/009: registry exposes a deterministic PascalCase module contract', function (): void {
        $registry = config('module.registry');
        $modules = config('module.list');

        expect($modules)->toBeArray()
            ->and($modules)->toHaveCount(19)
            ->and($modules)->toBe(array_values(array_unique($modules)))
            ->and($modules)->toBe(collect($modules)->sort()->values()->all())
            ->and($modules)->toContain('Academic', 'Journal', 'Partner', 'Report', 'Setting')
            ->and($registry)->toBeArray()
            ->and(array_keys($registry))->toBe($modules)
            ->and(config('module.test_dirs'))->toBe(['Providers', 'Stubs', 'Support'])
            ->and(config('module.paths'))->toMatchArray([
                'base' => app_path('Modules'),
                'views' => resource_path('views'),
                'routes' => base_path('routes/web'),
            ]);

        foreach ($modules as $module) {
            expect($module)->toMatch('/^[A-Z][A-Za-z0-9]+$/')
                ->and($registry[$module])->toBeArray();
        }
    });

    test('I1BCV-FR-MOD-010, I1BCV-UC-MOD-006: discovery subsystems expose independent enabled switches', function (): void {
        expect(config('module.livewire.enabled'))->toBeTrue()
            ->and(config('module.policies.enabled'))->toBeTrue()
            ->and(config('module.views.enabled'))->toBeTrue()
            ->and(config('module.livewire.directory'))->toBe('Livewire')
            ->and(config('module.policies.directory'))->toBe('Policies');
    });

    test('I1BCV-FR-MOD-030, I1BCV-FR-MOD-031, I1BCV-FR-MOD-032, I1BCV-FR-MOD-033, I1BCV-UC-MOD-004: route inclusion derives lowercase module paths and skips missing files', function (): void {
        $source = file_get_contents(base_path('routes/web.php'));

        expect($source)->toContain('ModuleManager::names()')
            ->and($source)->toContain('ModuleManager::routeFilePath($module)')
            ->and($source)->toContain('file_exists($file)')
            ->and(ModuleManager::routeFilePath('Academic'))
            ->toBe(base_path('routes/web/academic.php'))
            ->and(ModuleManager::routeFilePath('SysAdmin'))
            ->toBe(base_path('routes/web/sysadmin.php'));
    });

    test('I1BCV-FR-MOD-002: module roster changes require amendment evidence before registry synchronization', function (): void {
        $spec = file_get_contents(base_path('docs/specs/I1BCV-module-discovery.md'));
        $moduleIndex = file_get_contents(base_path('docs/refs/modules/index.md'));
        $pestSource = file_get_contents(base_path('tests/Pest.php'));
        $registeredModules = config('module.list');

        expect($spec)->toContain('#### FR-MOD-002 — Amendment before rename')
            ->toContain('governing-spec update + ADR')
            ->toContain('config/module.php')
            ->toContain('tests/Pest.php')
            ->toContain('docs/refs/modules/index.md')
            ->toContain('before any code is touched')
            ->and($moduleIndex)->toContain('All 19 modules are vertical slices')
            ->and($pestSource)->toContain('$dirs[] = $modulePath')
            ->and($registeredModules)->toHaveCount(19);
    });

    test('I1BCV-FR-MOD-040: non-module support test directories are present and registered', function (): void {
        foreach (config('module.test_dirs') as $directory) {
            expect(is_dir(base_path('tests/'.$directory)))->toBeTrue();
        }

        $source = file_get_contents(base_path('tests/Pest.php'));
        expect($source)->toContain("['Arch', 'Unit', 'Feature', 'Browser']")
            ->and($source)->toContain('$dirs[] = $modulePath');
    });
});
