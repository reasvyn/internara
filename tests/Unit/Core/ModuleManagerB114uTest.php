<?php

declare(strict_types=1);

use App\Modules\Core\Services\ModuleService;
use App\Modules\Core\Support\ModuleManager;

function b114uModuleFiles(): array
{
    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('app'), FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

describe('B114U: module manager accessors', function (): void {
    test('B114U-FR-MGR-001: names returns the registered module list (also FR-MGR-030, DD-MGR-005)', function (): void {
        expect(ModuleManager::names())->toBe(config('module.list'))
            ->and(ModuleManager::names())->toContain('Core');
    });

    test('B114U-FR-MGR-002: membership checks are strict (also NFR-MGR-004)', function (): void {
        expect(ModuleManager::isModule('User'))->toBeTrue()
            ->and(ModuleManager::isModule('user'))->toBeFalse()
            ->and(ModuleManager::isModule('Nope'))->toBeFalse()
            ->and(ModuleManager::isRegisteredDirectory('USER'))->toBeTrue()
            ->and(ModuleManager::isRegisteredDirectory('Nope'))->toBeFalse();
    });

    test('B114U-FR-MGR-003: registry maps modules to domains (also FR-MGR-004)', function (): void {
        expect(ModuleManager::registry()['User'])->toContain('UserManagement')
            ->and(ModuleManager::domains('User'))->toContain('UserManagement')
            ->and(ModuleManager::domains('Nope'))->toBe([])
            ->and(ModuleManager::submodules('User'))->toBe(ModuleManager::domains('User'));
    });

    test('B114U-FR-MGR-005: test directories come from configuration (also FR-MGR-006)', function (): void {
        expect(ModuleManager::testDirs())->toBe(config('module.test_dirs'))
            ->and(ModuleManager::basePath())->toBeString()
            ->and(ModuleManager::viewsPath())->toBeString()
            ->and(ModuleManager::routesPath())->toBeString();
    });

    test('B114U-FR-MGR-007: typed boolean accessors never expose raw config (also UC-MGR-004, DD-MGR-004)', function (): void {
        expect(ModuleManager::policiesEnabled())->toBeBool()
            ->and(ModuleManager::livewireEnabled())->toBeBool()
            ->and(ModuleManager::viewsEnabled())->toBeBool()
            ->and(ModuleManager::factoriesEnabled())->toBeBool();
    });

    test('B114U-FR-MGR-008: discovery directories and exclusions are exposed (also FR-MGR-009, FR-MGR-010)', function (): void {
        expect(ModuleManager::livewireDirectory())->toBeString()
            ->and(ModuleManager::livewireExcludePaths())->toBeArray()
            ->and(ModuleManager::policiesDirectory())->toBeString()
            ->and(ModuleManager::policiesExcludePaths())->toBeArray()
            ->and(ModuleManager::policyModelNamespace())->toBeString()
            ->and(ModuleManager::viewsExcludeDirectories())->toBeArray();
    });

    test('B114U-FR-MGR-011: route paths follow the lowercase convention (also FR-MGR-035)', function (): void {
        expect(ModuleManager::routeFilePath('User'))->toContain('user')
            ->and(ModuleManager::routeFilePath('SysAdmin'))->toContain('sysadmin')
            ->and(file_exists(ModuleManager::routeFilePath('User')))->toBeTrue();
    });

    test('B114U-FR-MGR-013: the manager performs no filesystem scanning', function (): void {
        $source = file_get_contents((new ReflectionClass(ModuleManager::class))->getFileName());

        expect($source)->not->toContain('scandir')
            ->and($source)->not->toContain('glob(')
            ->and(ModuleManager::names())->not->toBe([]);
    });

    test('B114U-FR-MGR-014: every accessor is public static with no constructor (also DD-MGR-001)', function (): void {
        $reflection = new ReflectionClass(ModuleManager::class);

        expect($reflection->getConstructor())->toBeNull();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== ModuleManager::class) {
                continue;
            }

            expect($method->isStatic())->toBeTrue();
        }

        expect(ModuleManager::names())->not->toBe([]);
    });

    test('B114U-FR-MGR-026: the router loads modules through the gateway only (also UC-MGR-001)', function (): void {
        $routes = file_get_contents(base_path('routes/web.php'));

        expect($routes)->toContain('ModuleManager::names()')
            ->and($routes)->toContain('ModuleManager::routeFilePath')
            ->and($routes)->not->toContain("config('module.");
    });

    test('B114U-FR-MGR-027: the provider injects the service behind feature flags', function (): void {
        $provider = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));

        expect($provider)->toContain('ModuleService')
            ->and($provider)->toContain('ModuleManager::')
            ->and(ModuleManager::livewireEnabled())->toBeBool();
    });

    test('B114U-NFR-MGR-001: module config reads live only in the manager', function (): void {
        $violations = [];

        foreach (b114uModuleFiles() as $file) {
            if (str_ends_with($file, 'Support/ModuleManager.php')) {
                continue;
            }

            if (str_contains((string) file_get_contents($file), "config('module.")) {
                $violations[] = $file;
            }
        }

        expect($violations)->toBe([]);
    });

    test('B114U-FR-MGR-032: names match the auto-discovered directory listing', function (): void {
        $dirs = [];

        foreach (scandir(base_path('app/Modules')) as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_dir(base_path('app/Modules/'.$entry))) {
                $dirs[] = $entry;
            }
        }

        sort($dirs);

        expect(ModuleManager::names())->toBe($dirs);
    });

    test('B114U-FR-MGR-033: the roster resolves exactly nineteen frozen modules', function (): void {
        expect(ModuleManager::names())->toBe([
            'Academic', 'Assessment', 'Assignment', 'Auth', 'Certification', 'Core',
            'Document', 'Enrollment', 'Evaluation', 'Incident', 'Journal', 'Partner',
            'Program', 'Report', 'Setting', 'Setup', 'SysAdmin', 'UI', 'User',
        ]);
    });

    test('B114U-FR-MGR-034: registry, pest directories, and module docs agree', function (): void {
        $index = file_get_contents(base_path('docs/refs/modules/index.md'));
        $pest = file_get_contents(base_path('tests/Pest.php'));

        foreach (['User', 'Auth', 'Core'] as $module) {
            expect($index)->toContain($module);
        }

        expect($pest)->toContain('scandir')
            ->and(ModuleManager::names())->toContain('User');
    });

    test('B114U-FR-MGR-031: no superseded discover service remains (also DD-MGR-002)', function (): void {
        $leftovers = [];

        foreach (b114uModuleFiles() as $file) {
            $base = basename($file);

            if (preg_match('/(Legacy|Old).*(Discover|Discovery)/', $base)) {
                $leftovers[] = $file;
            }
        }

        expect($leftovers)->toBe([])
            ->and(class_exists(ModuleService::class))->toBeTrue()
            ->and(ModuleManager::names())->not->toBe([]);
    });
});
