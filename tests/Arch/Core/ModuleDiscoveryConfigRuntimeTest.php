<?php

declare(strict_types=1);

use App\Modules\Core\Support\ModuleManager;

describe('I1BCV/B114U: module configuration contract', function (): void {
    test('I1BCV-FR-MOD-003/004/005/006/007/008/009 and B114U-FR-MGR-030/032/033/034: config derives the current singular roster and domains from disk', function (): void {
        $directories = collect(scandir(app_path('Modules')))
            ->reject(fn (string $directory): bool => in_array($directory, ['.', '..'], true))
            ->filter(fn (string $directory): bool => is_dir(app_path('Modules/'.$directory)))
            ->sort()
            ->values()
            ->all();

        expect(config('module.list'))->toBe($directories)
            ->and(ModuleManager::names())->toBe($directories)
            ->and(array_keys(config('module.registry')))->toBe($directories)
            ->and(config('module.test_dirs'))->toBe(['Providers', 'Stubs', 'Support'])
            ->and(config('module.paths'))->toMatchArray([
                'base' => app_path('Modules'),
                'views' => resource_path('views'),
                'routes' => base_path('routes/web'),
            ]);

        foreach ($directories as $module) {
            expect($module)->toMatch('/^[A-Z][A-Za-z0-9]+$/')
                ->and(ModuleManager::domains($module))->toBe(config('module.registry.'.$module));
        }
    });

    test('I1BCV-FR-MOD-010 and B114U-FR-MGR-007: each discovery subsystem reads an independent enabled flag', function (): void {
        config([
            'module.livewire.enabled' => false,
            'module.policies.enabled' => true,
            'module.views.enabled' => false,
        ]);

        expect(ModuleManager::livewireEnabled())->toBeFalse()
            ->and(ModuleManager::policiesEnabled())->toBeTrue()
            ->and(ModuleManager::viewsEnabled())->toBeFalse();
    });
});
