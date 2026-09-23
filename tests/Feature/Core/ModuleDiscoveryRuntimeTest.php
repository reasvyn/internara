<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permission\Policies\UserPolicy;
use App\Modules\Core\Console\Commands\ModuleDiscoverCommand;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Core\Services\ModuleService;
use App\Modules\Core\Support\ModuleManager;
use App\Modules\User\Models\User;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

uses(LazilyRefreshDatabase::class);

function clearModuleDiscoveryCaches(): void
{
    foreach (['module_livewire', 'module_policies', 'module_views'] as $key) {
        Cache::forget(config('cache-keys.'.$key));
    }
}

describe('I1BCV/B114U: module discovery runtime contracts', function (): void {
    test('I1BCV-FR-MOD-011, I1BCV-FR-MOD-015, I1BCV-UC-MOD-002, I1BCV-UC-MOD-003, B114U-FR-MGR-015: Livewire discovery registers only valid registered components', function (): void {
        clearModuleDiscoveryCaches();

        app(ModuleService::class)->discoverLivewireComponents();

        $components = Cache::get(config('cache-keys.module_livewire'));

        expect($components)->toBeArray()
            ->and($components)->toHaveKey('user.user-management.user-manager')
            ->and($components)->not->toHaveKey('user.user-management.concerns.downloads-account-slips')
            ->and($components)->not->toHaveKey('user.user-management.downloads-account-slips');

        foreach ($components as $alias => $class) {
            expect($class)->toBeString()
                ->and(is_subclass_of($class, Component::class))->toBeTrue()
                ->and($alias)->not->toContain('concerns')
                ->and($alias)->not->toContain('traits')
                ->and(ModuleManager::isModule(Str::studly(explode('.', $alias)[0])))->toBeTrue();
        }
    });

    test('I1BCV-FR-MOD-018, I1BCV-FR-MOD-024 and B114U-FR-MGR-017: policy discovery binds local models and preserves the explicit cross-module binding', function (): void {
        clearModuleDiscoveryCaches();

        app(ModuleService::class)->discoverPolicies();

        $policies = Cache::get(config('cache-keys.module_policies'));

        expect($policies)->toBeArray()
            ->and($policies)->not->toBeEmpty()
            ->and($policies)->not->toHaveKey(User::class)
            ->and(Gate::getPolicyFor(User::class))->toBeInstanceOf(UserPolicy::class);

        foreach ($policies as $model => $policy) {
            expect($policy)->toEndWith('Policy')
                ->and(is_subclass_of($policy, BasePolicy::class))->toBeTrue()
                ->and($model)->toStartWith('App\\Modules\\');
        }
    });

    test('I1BCV-FR-MOD-025, I1BCV-UC-MOD-001, B114U-FR-MGR-018: view discovery registers only module namespaces and excludes shared directories', function (): void {
        clearModuleDiscoveryCaches();

        app(ModuleService::class)->registerBladeNamespaces();

        $namespaces = Cache::get(config('cache-keys.module_views'));
        $hints = View::getFinder()->getHints();

        expect($namespaces)->toBeArray()
            ->and($namespaces)->not->toBeEmpty()
            ->and($hints)->toHaveKey('user')
            ->and(View::exists('user::dashboard.index'))->toBeTrue();

        foreach (ModuleManager::viewsExcludeDirectories() as $excluded) {
            expect($namespaces)->not->toContain(['name' => $excluded, 'path' => resource_path('views/'.$excluded)]);
        }
    });

    test('I1BCV-FR-MOD-034, I1BCV-FR-MOD-037, I1BCV-UC-MOD-005, B114U-FR-MGR-028, B114U-UC-MGR-003, B114U-NFR-MOD-002: module:discover clears every cache, rediscoveries, logs completion, and renders translated tasks', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Cache::put(config('cache-keys.module_livewire'), ['stale' => 'Stale\\Component'], 600);
        Cache::put(config('cache-keys.module_policies'), ['stale' => 'Stale\\Policy'], 600);
        Cache::put(config('cache-keys.module_views'), [['name' => 'stale', 'path' => '/tmp/stale']], 600);

        expect(Artisan::call('module:discover'))->toBe(0)
            ->and(Cache::get(config('cache-keys.module_livewire')))->not->toHaveKey('stale')
            ->and(Cache::get(config('cache-keys.module_policies')))->not->toHaveKey('stale')
            ->and(Cache::get(config('cache-keys.module_views')))->not->toContain(['name' => 'stale', 'path' => '/tmp/stale'])
            ->and(Artisan::output())->toContain(__('setup.cli.tasks.discover_complete'))
            ->and(DB::table('activity_log')->where('event', 'module.discover.completed')->exists())->toBeTrue();
    });

    test('I1BCV-FR-MOD-036 and B114U-FR-MGR-028: discovery failure returns non-zero and logs failure without exposing an exception', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        config(['module.paths.base' => base_path('missing-module-root')]);

        expect(Artisan::call('module:discover'))->toBe(1)
            ->and(Artisan::output())->toContain(__('setup.cli.tasks.discover_failed'))
            ->and(DB::table('activity_log')->where('event', 'module.discover.failed')->exists())->toBeTrue();
    });

    test('I1BCV-FR-MOD-035: command refuses discovery when AppServiceProvider is not loaded', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $service = app(ModuleService::class);

        $application = Mockery::mock(Application::class);
        $application->shouldReceive('getLoadedProviders')->once()->andReturn([]);

        $command = new ModuleDiscoverCommand($service);
        $command->setLaravel($application);
        $command->setOutput(new OutputStyle(new ArgvInput, new BufferedOutput));

        expect($command->handle())->toBe(1)
            ->and(DB::table('activity_log')->where('event', 'module.discover.failed')->exists())->toBeTrue();
    });
});
