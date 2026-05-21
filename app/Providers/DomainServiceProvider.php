<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Core\Policies\BasePolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class DomainServiceProvider extends ServiceProvider
{
    private const DOMAIN_PATH = __DIR__.'/../Domain';

    public function boot(): void
    {
        if (config('domain.policies.enabled', true)) {
            $this->discoverPolicies();
        }

        if (config('domain.livewire.enabled', true)) {
            $this->discoverLivewireComponents();
        }

        if (config('domain.views.enabled', true)) {
            $this->registerBladeNamespaces();
        }
    }

    protected function registerCommands(): void
    {
        if (! config('domain.commands.enabled', true)) {
            return;
        }

        $directory = config('domain.commands.directory', 'Console/Commands');

        $this->commands([
            __DIR__.'/../Domain/Core/Console/Commands',
            __DIR__.'/../Domain/Setup/Console/Commands',
            __DIR__.'/../Domain/Auth/Console/Commands',
            __DIR__.'/../Domain/Admin/Console/Commands',
            __DIR__.'/../Domain/User/Console/Commands',
        ]);
    }

    /**
     * Auto-discover and register all Livewire components from each domain's Livewire module.
     */
    private function discoverLivewireComponents(): void
    {
        $domainDir = realpath(self::DOMAIN_PATH);

        if ($domainDir === false) {
            return;
        }

        $directory = config('domain.livewire.directory', 'Livewire');
        $excludePaths = config('domain.livewire.exclude_paths', ['Concerns', 'Traits']);
        $registered = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainDir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($phpFiles as $fileList) {
            $filePath = $fileList[0];

            if (! str_contains($filePath, '/'.$directory.'/')) {
                continue;
            }

            $shouldSkip = false;
            foreach ($excludePaths as $excluded) {
                if (str_contains($filePath, '/'.$excluded.'/')) {
                    $shouldSkip = true;
                    break;
                }
            }

            if ($shouldSkip) {
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

            preg_match('#/Domain/([^/]+)/'.$directory.'/#', $filePath, $domainMatch);
            $domain = $domainMatch[1] ?? '';

            $alias = Str::kebab($domain).'.'.Str::kebab($className);

            Livewire::component($alias, $fqcn);

            $registered[] = $alias;
        }

        if ($registered !== []) {
            logger()->debug('DomainServiceProvider: auto-registered Livewire components', [
                'count' => count($registered),
            ]);
        }
    }

    /**
     * Auto-discover and register all policies from each domain's Policies directory.
     */
    private function discoverPolicies(): void
    {
        $domainDir = realpath(self::DOMAIN_PATH);

        if ($domainDir === false) {
            return;
        }

        $directory = config('domain.policies.directory', 'Policies');
        $excludePaths = config('domain.policies.exclude_paths', ['Concerns', 'Traits']);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainDir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($phpFiles as $fileList) {
            $filePath = $fileList[0];

            if (! str_contains($filePath, '/'.$directory.'/')) {
                continue;
            }

            $shouldSkip = false;
            foreach ($excludePaths as $excluded) {
                if (str_contains($filePath, '/'.$excluded.'/')) {
                    $shouldSkip = true;
                    break;
                }
            }

            if ($shouldSkip) {
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

            preg_match('#/Domain/([^/]+)/'.$directory.'/#', $filePath, $domainMatch);
            $domain = $domainMatch[1] ?? '';

            $modelName = preg_replace('/Policy$/', '', $className);
            $modelClass = "App\\Domain\\{$domain}\\Models\\{$modelName}";

            if (! class_exists($modelClass)) {
                continue;
            }

            Gate::policy($modelClass, $policyClass);
        }
    }

    /**
     * Register Blade anonymous component paths for each domain view directory.
     */
    private function registerBladeNamespaces(): void
    {
        $viewsDir = realpath(config('domain.paths.views', self::DOMAIN_PATH.'/../../resources/views'));

        if ($viewsDir === false) {
            return;
        }

        $excluded = config('domain.views.exclude_directories', [
            'components', 'emails', 'errors', 'layouts', 'mcp', 'pdf', 'vendor',
        ]);

        $domainDirs = glob($viewsDir.'/*', GLOB_ONLYDIR);

        foreach ($domainDirs as $dir) {
            $name = basename($dir);

            if (in_array($name, $excluded, true)) {
                continue;
            }

            if (is_dir($dir)) {
                Blade::anonymousComponentPath($dir, $name);
            }
        }
    }
}
