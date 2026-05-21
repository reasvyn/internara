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

    private const VIEWS_PATH = __DIR__.'/../../resources/views';

    /**
     * Bootstrap domain services.
     */
    public function boot(): void
    {
        $this->discoverPolicies();

        $this->discoverLivewireComponents();

        $this->registerBladeNamespaces();
    }

    /**
     * Auto-discover and register all Livewire components from each domain's Livewire module.
     *
     * Components are registered with the alias pattern: {domain}.{kebab-case-name}
     * Concerns/traits and non-Component classes are skipped.
     */
    private function discoverLivewireComponents(): void
    {
        $domainDir = realpath(self::DOMAIN_PATH);

        if ($domainDir === false) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainDir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        $registered = [];

        foreach ($phpFiles as $fileList) {
            $filePath = $fileList[0];

            if (! str_contains($filePath, '/Livewire/')) {
                continue;
            }

            if (str_contains($filePath, '/Concerns/') || str_contains($filePath, '/Traits/')) {
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

            preg_match('#/Domain/([^/]+)/Livewire/#', $filePath, $domainMatch);
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
     *
     * Convention: {Model}Policy in a domain's Policies/ directory gates
     * {Model} in the same domain's Models/ directory.
     * Skips policies that don't have a matching model.
     */
    private function discoverPolicies(): void
    {
        $domainDir = realpath(self::DOMAIN_PATH);

        if ($domainDir === false) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainDir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($phpFiles as $fileList) {
            $filePath = $fileList[0];

            if (! str_contains($filePath, '/Policies/')) {
                continue;
            }

            if (str_contains($filePath, '/Concerns/') || str_contains($filePath, '/Traits/')) {
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

            preg_match('#/Domain/([^/]+)/Policies/#', $filePath, $domainMatch);
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
     *
     * Each resources/views/{domain}/ directory is registered as the
     * x-{domain}::* Blade component namespace.
     */
    private function registerBladeNamespaces(): void
    {
        $viewsDir = realpath(self::VIEWS_PATH);

        if ($viewsDir === false) {
            return;
        }

        $domainDirs = glob($viewsDir.'/*', GLOB_ONLYDIR);

        $excluded = ['components', 'emails', 'errors', 'layouts', 'mcp', 'pdf', 'vendor'];

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
