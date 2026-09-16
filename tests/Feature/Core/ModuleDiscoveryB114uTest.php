<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Core\Services\ModuleService;
use App\Modules\Core\Support\ModuleManager;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

function b114uDiscover(): array
{
    Cache::forget(config('cache-keys.module_livewire'));
    Cache::forget(config('cache-keys.module_policies'));
    Cache::forget(config('cache-keys.module_views'));

    app(ModuleService::class)->discoverLivewireComponents();
    app(ModuleService::class)->discoverPolicies();
    app(ModuleService::class)->registerBladeNamespaces();

    return Cache::get(config('cache-keys.module_livewire'), []);
}

describe('B114U: module discovery runtime', function (): void {
    test('B114U-FR-MGR-016: livewire discovery registers components (also FR-MGR-021, FR-MGR-022, FR-MGR-023, UC-MGR-002, I1BCV-FR-MOD-011, I1BCV-FR-MOD-012, I1BCV-FR-MOD-013, I1BCV-FR-MOD-014, I1BCV-FR-MOD-016, I1BCV-FR-MOD-017)', function (): void {
        $map = b114uDiscover();
        $names = ModuleManager::names();

        expect($map)->not->toBe([])
            ->and($map)->toHaveKey('user.user-management.user-manager');

        foreach ($map as $alias => $fqcn) {
            $module = Str::studly(explode('.', $alias)[0]);

            expect($names)->toContain($module);
            expect($alias)->not->toContain('concerns')
                ->and($alias)->not->toContain('traits');
        }
    });

    test('B114U-FR-MGR-017: policy discovery binds same-module models (also FR-MGR-024, NFR-MGR-008, I1BCV-FR-MOD-018, I1BCV-FR-MOD-019, I1BCV-FR-MOD-020, I1BCV-FR-MOD-021, I1BCV-FR-MOD-022, I1BCV-FR-MOD-023)', function (): void {
        b114uDiscover();

        // The shared User model resolves through the cross-module binding in AppServiceProvider.
        expect(Gate::getPolicyFor(User::class))->toBeInstanceOf(BasePolicy::class);

        foreach ([Department::factory()->make(), Company::factory()->make()] as $model) {
            $policy = Gate::getPolicyFor($model::class);

            expect($policy)->toBeInstanceOf(BasePolicy::class);

            $modelPath = (new ReflectionClass($model))->getFileName();
            $policyPath = (new ReflectionClass($policy))->getFileName();

            expect(Str::before($policyPath, '/Policies/'))->toBe(Str::before($modelPath, '/Models/'));
        }
    });

    test('B114U-FR-MGR-018: blade namespaces register with exclusions honored (also FR-MGR-025, I1BCV-FR-MOD-025, I1BCV-FR-MOD-026, I1BCV-FR-MOD-027, I1BCV-FR-MOD-028, I1BCV-FR-MOD-029)', function (): void {
        b114uDiscover();

        $hints = View::getFinder()->getHints();
        $service = file_get_contents(base_path('app/Modules/Core/Services/ModuleService.php'));

        expect($hints)->toHaveKey('ui')
            ->and(View::exists('ui::layouts.app'))->toBeTrue()
            ->and($service)->toContain('viewsExcludeDirectories');

        foreach (ModuleManager::viewsExcludeDirectories() as $excluded) {
            foreach ($hints[$excluded] ?? [] as $path) {
                expect($path)->not->toBe(base_path('resources/views/'.$excluded));
            }
        }
    });

    test('B114U-FR-MGR-020: discovery caches behind registry keys (also NFR-MGR-006, I1BCV-FR-MOD-017, I1BCV-FR-MOD-023, I1BCV-FR-MOD-029)', function (): void {
        Cache::forget(config('cache-keys.module_livewire'));

        app(ModuleService::class)->discoverLivewireComponents();

        expect(Cache::get(config('cache-keys.module_livewire')))->toBeArray()
            ->and(Cache::get(config('cache-keys.module_livewire')))->not->toBe([]);
    });

    test('B114U-FR-MGR-028: the discover command refreshes caches and logs (also UC-MGR-003, NFR-MGR-007, FR-MGR-029, DD-MGR-002)', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Cache::put(config('cache-keys.module_livewire'), ['stale' => 'Stale\\Component'], 600);

        expect(Artisan::call('module:discover'))->toBe(0);
        expect(Cache::get(config('cache-keys.module_livewire')))->not->toBe(['stale' => 'Stale\\Component']);
        expect(DB::table('activity_log')->where('event', 'module.discover.completed')->exists())->toBeTrue();
    });
});
