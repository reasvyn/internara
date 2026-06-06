<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Contracts\SendsNotifications;
use App\Core\Contracts\SettingsStore;
use App\Core\Policies\BasePolicy;
use App\Core\Support\LangChecker;
use App\Settings\Support\Settings;
use App\Setup\Events\SetupFinalized;
use App\Setup\Listeners\LogSetupFinalized;
use App\Support\CacheKeys;
use App\User\Notifications\Actions\SendNotificationAction;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__.'/..';

    public function register(): void
    {
        if ($this->app->hasDebugModeEnabled()) {
            $this->app->extend('translator', fn ($translator) => tap(
                new LangChecker($translator->getLoader(), $translator->getLocale()),
                fn (LangChecker $checker) => $checker->setFallback($translator->getFallback()),
            ));
        }

        $this->app->bind(SendsNotifications::class, SendNotificationAction::class);

        $this->app->singleton(SettingsStore::class, function () {
            return new class implements SettingsStore
            {
                public function get(string $key, mixed $default = null): mixed
                {
                    return Settings::get($key, $default);
                }
            };
        });
    }

    public function boot(): void
    {
        RateLimiter::for('admin', fn () => Limit::perMinute(60));
        RateLimiter::for('global', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        if (config('module.policies.enabled', true)) {
            $this->discoverPolicies();
        }

        if (config('module.livewire.enabled', true)) {
            $this->discoverLivewireComponents();
        }

        if (config('module.views.enabled', true)) {
            $this->registerBladeNamespaces();
        }

        Event::listen(
            SetupFinalized::class,
            [LogSetupFinalized::class, 'handle'],
        );
    }

    public function discoverLivewireComponents(): void
    {
        $components = Cache::remember(CacheKeys::MODULE_LIVEWIRE, 86400, function () {
            $result = [];
            $moduleDir = realpath(self::MODULE_PATH);
            if ($moduleDir === false) {
                return $result;
            }

            $directory = config('module.livewire.directory', 'Livewire');
            $excludePaths = config('module.livewire.exclude_paths', ['Concerns', 'Traits']);
            $files = $this->scanPhpFiles($moduleDir);

            foreach ($files as $filePath) {
                if (! str_contains($filePath, '/'.$directory.'/')) {
                    continue;
                }

                if ($this->isExcludedPath($filePath, $excludePaths)) {
                    continue;
                }

                $content = file_get_contents($filePath);
                if (! preg_match('/^namespace\s+(.+?);$/m', $content, $nsMatch)) {
                    continue;
                }

                $className = basename($filePath, '.php');
                $fqcn = $nsMatch[1].'\\'.$className;

                if (! is_subclass_of($fqcn, 'Livewire\Component')) {
                    continue;
                }

                $relativePath = str_replace($moduleDir.'/', '', $filePath);
                $parts = explode('/', $relativePath);

                if (($parts[0] ?? '') === $directory) {
                    continue;
                }

                // If structure: Module/Submodule/Livewire/Class.php
                // index: 0=Module, 1=Submodule, 2=Livewire, 3=Class.php (or index 1 is Livewire)
                $module = $parts[0] ?? '';
                $submodule = ($parts[1] !== $directory) ? ($parts[1] ?? '') : '';

                $alias = $submodule
                    ? Str::kebab($module).'.'.Str::kebab($submodule).'.'.Str::kebab($className)
                    : Str::kebab($module).'.'.Str::kebab($className);

                $result[$alias] = $fqcn;
            }

            return $result;
        });

        foreach ($components as $alias => $fqcn) {
            Livewire::component($alias, $fqcn);
        }
    }

    public function discoverPolicies(): void
    {
        $policies = Cache::remember(CacheKeys::MODULE_POLICIES, 86400, function () {
            $result = [];
            $moduleDir = realpath(self::MODULE_PATH);
            if ($moduleDir === false) {
                return $result;
            }

            $directory = config('module.policies.directory', 'Policies');
            $excludePaths = config('module.policies.exclude_paths', ['Concerns', 'Traits']);
            $files = $this->scanPhpFiles($moduleDir);

            foreach ($files as $filePath) {
                if (! str_contains($filePath, '/'.$directory.'/')) {
                    continue;
                }

                if ($this->isExcludedPath($filePath, $excludePaths)) {
                    continue;
                }

                $className = basename($filePath, '.php');
                if (! str_ends_with($className, 'Policy')) {
                    continue;
                }

                $content = file_get_contents($filePath);
                if (! preg_match('/^namespace\s+(.+?);$/m', $content, $nsMatch)) {
                    continue;
                }

                $policyClass = $nsMatch[1].'\\'.$className;
                if (! is_subclass_of($policyClass, BasePolicy::class)) {
                    continue;
                }

                $relativePath = str_replace($moduleDir.'/', '', $filePath);
                $parts = explode('/', $relativePath);
                $module = $parts[0] ?? '';
                $submodule = ($parts[1] !== $directory) ? ($parts[1] ?? '') : '';

                $modelName = preg_replace('/Policy$/', '', $className);
                $modelClass = $submodule
                    ? "App\\{$module}\\{$submodule}\\Models\\{$modelName}"
                    : "App\\{$module}\\Models\\{$modelName}";

                if (! class_exists($modelClass)) {
                    continue;
                }

                $result[$modelClass] = $policyClass;
            }

            return $result;
        });

        foreach ($policies as $modelClass => $policyClass) {
            Gate::policy($modelClass, $policyClass);
        }
    }

    public function registerBladeNamespaces(): void
    {
        $namespaces = Cache::remember(CacheKeys::MODULE_VIEWS, 86400, function () {
            $result = [];
            $viewsDir = realpath(config('module.paths.views', self::MODULE_PATH.'/../resources/views'));
            if ($viewsDir === false) {
                return $result;
            }

            $excluded = config('module.views.exclude_directories', [
                'components', 'emails', 'errors', 'mcp', 'pdf', 'vendor',
            ]);

            $moduleDirs = glob($viewsDir.'/*', GLOB_ONLYDIR);
            foreach ($moduleDirs as $dir) {
                $name = basename($dir);
                if (in_array($name, $excluded, true)) {
                    continue;
                }
                $result[] = ['name' => $name, 'path' => $dir];
            }

            return $result;
        });

        foreach ($namespaces as $ns) {
            if (is_dir($ns['path'])) {
                Blade::anonymousComponentPath($ns['path'], $ns['name']);
                View::addNamespace($ns['name'], $ns['path']);
            }
        }
    }

    private function scanPhpFiles(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function isExcludedPath(string $filePath, array $excludePaths): bool
    {
        foreach ($excludePaths as $excluded) {
            if (str_contains($filePath, '/'.$excluded.'/')) {
                return true;
            }
        }

        return false;
    }
}
