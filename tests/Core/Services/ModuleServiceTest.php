<?php

declare(strict_types=1);

use App\Core\Services\ModuleService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

function moduleServiceWriteFixtures(): void
{
    $livewireDir = app_path('FixtureModule/Livewire');
    File::ensureDirectoryExists($livewireDir.'/Concerns');
    File::ensureDirectoryExists(app_path('FixtureModule/Models'));
    File::ensureDirectoryExists(app_path('FixtureModule/Policies'));
    File::ensureDirectoryExists(resource_path('views/FixtureModule'));

    File::put(
        $livewireDir.'/FixtureWidget.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\FixtureModule\\Livewire;\n\nuse Livewire\\Component;\n\nfinal class FixtureWidget extends Component\n{\n    public function render(): string\n    {\n        return '<div>fixture</div>';\n    }\n}\n",
    );
    File::put(
        $livewireDir.'/Concerns/SkippedWidget.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\FixtureModule\\Livewire\\Concerns;\n\nuse Livewire\\Component;\n\nfinal class SkippedWidget extends Component\n{\n    public function render(): string\n    {\n        return '<div>skipped</div>';\n    }\n}\n",
    );
    File::put(
        $livewireDir.'/NotAComponent.php',
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\FixtureModule\\Livewire;\n\nfinal class NotAComponent\n{\n}\n",
    );
    File::put(
        app_path('FixtureModule/Models/FixtureModel.php'),
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\FixtureModule\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nfinal class FixtureModel extends Model\n{\n    protected \$table = 'fixture_models';\n}\n",
    );
    File::put(
        app_path('FixtureModule/Policies/FixtureModelPolicy.php'),
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\FixtureModule\\Policies;\n\nuse App\\Core\\Policies\\BasePolicy;\n\nfinal class FixtureModelPolicy extends BasePolicy\n{\n}\n",
    );
    File::put(resource_path('views/FixtureModule/dashboard.blade.php'), '<div>fixture</div>');
}

function moduleServiceConfigure(): ModuleService
{
    return new ModuleService(Cache::store());
}

afterEach(function () {
    config()->set('module.list', $this->originalModules ?? config('module.list'));
    Cache::forget('module.discovered_livewire');
    Cache::forget('module.discovered_policies');
    Cache::forget('module.discovered_views');
    File::deleteDirectory(app_path('FixtureModule'));
    File::deleteDirectory(resource_path('views/FixtureModule'));
});

beforeEach(function () {
    $this->originalModules = config('module.list');
    config()->set('module.list', array_merge($this->originalModules, ['FixtureModule']));
    Cache::forget('module.discovered_livewire');
    Cache::forget('module.discovered_policies');
    Cache::forget('module.discovered_views');
    moduleServiceWriteFixtures();
});

test('I1BCV-FR-LW3/FR-LW5: Livewire discovery registers Component classes with kebab aliases', function () {
    moduleServiceConfigure()->discoverLivewireComponents();

    $discovered = Cache::get('module.discovered_livewire');

    expect($discovered)->toHaveKey('fixture-module.fixture-widget');
    expect($discovered['fixture-module.fixture-widget'])->toBe('App\FixtureModule\Livewire\FixtureWidget');
    expect($discovered)->not->toHaveKey('fixture-module.not-a-component');
});

test('I1BCV-FR-LW2: Livewire discovery skips excluded Concerns directories', function () {
    moduleServiceConfigure()->discoverLivewireComponents();

    $discovered = Cache::get('module.discovered_livewire');

    expect($discovered)->not->toHaveKey('fixture-module.concerns.skipped-widget');
    expect($discovered)->not->toHaveKey('fixture-module.skipped-widget');
});

test('I1BCV-FR-LW6: Livewire discovery results are cached', function () {
    $service = moduleServiceConfigure();
    $service->discoverLivewireComponents();

    expect(Cache::has('module.discovered_livewire'))->toBeTrue();
});

test('I1BCV-FR-LW1/FR-LW7: discovery only scans registered modules', function () {
    $service = moduleServiceConfigure();
    $service->discoverLivewireComponents();

    $discovered = Cache::get('module.discovered_livewire');
    $aliases = array_keys($discovered);

    $prefixes = collect(config('module.list'))->map(fn (string $module) => Str::kebab($module));

    foreach ($aliases as $alias) {
        expect($alias)->toMatch('/^[a-z0-9-]+\./');
        expect($prefixes->contains(fn (string $prefix) => str_starts_with($alias, $prefix.'.')))
            ->toBeTrue("{$alias} must belong to a registered module");
    }
});

test('I1BCV-FR-MR1-FR-MR8: policy discovery maps policies to their models', function () {
    moduleServiceConfigure()->discoverPolicies();

    $discovered = Cache::get('module.discovered_policies');
    $modelClass = 'App\FixtureModule\Models\FixtureModel';
    $policyClass = 'App\FixtureModule\Policies\FixtureModelPolicy';

    expect($discovered)->toHaveKey($modelClass);
    expect($discovered[$modelClass])->toBe($policyClass);

    $policy = Gate::getPolicyFor(new $modelClass);
    expect($policy)->toBeInstanceOf($policyClass);
});

test('I1BCV-FR-V1/FR-V3/FR-V5: Blade namespaces are registered only for registered modules', function () {
    moduleServiceConfigure()->registerBladeNamespaces();

    $discovered = Cache::get('module.discovered_views');
    $paths = collect($discovered)->pluck('name');

    expect($paths)->toContain('FixtureModule');

    $hints = View::getFinder()->getHints();
    expect($hints)->toHaveKey('FixtureModule');
    expect($hints['FixtureModule'])->toContain(resource_path('views/FixtureModule'));
});

test('I1BCV-FR-V4: Blade namespace discovery results are cached', function () {
    moduleServiceConfigure()->registerBladeNamespaces();

    expect(Cache::has('module.discovered_views'))->toBeTrue();
});
