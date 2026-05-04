<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * Thin registrar for interface-to-implementation bindings.
 *
 * All binding declarations live in `config/bindings.php`.
 * This provider only reads configuration and registers bindings — no inline logic.
 */
class BindServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerDefaultBindings();
        $this->registerAutobindings();
        $this->registerContextualBindings();
    }

    /**
     * Register explicit interface-to-implementation bindings from config.
     */
    protected function registerDefaultBindings(): void
    {
        $method = config('bindings.bind_as_singleton', false) ? 'singleton' : 'bind';
        $bindings = config('bindings.default', []);

        foreach ($bindings as $abstract => $concrete) {
            $this->app->{$method}($abstract, $concrete);
        }
    }

    /**
     * Auto-discover bindings from `App/Contracts/` (centralized) and
     * `App/{Domain}/Contracts/` (domain-scoped) using configured patterns.
     */
    protected function registerAutobindings(): void
    {
        if (! config('bindings.autobind', true)) {
            return;
        }

        $ignoredNamespaces = config('bindings.ignored_namespaces', []);
        $patterns = config('bindings.patterns', []);
        $method = config('bindings.bind_as_singleton', false) ? 'singleton' : 'bind';

        $allBindings = [];

        // Scan centralized contracts: App/Contracts/{Domain}/
        $centralizedPath = app_path('Contracts');
        if (is_dir($centralizedPath)) {
            $allBindings += $this->scanDirectory(
                path: $centralizedPath,
                patterns: $patterns,
                ignoredNamespaces: $ignoredNamespaces,
            );
        }

        // Scan domain-scoped contracts: App/Domain/{Domain}/Contracts/
        $domainPath = app_path('Domain');
        if (is_dir($domainPath)) {
            foreach (File::directories($domainPath) as $domainDir) {
                $contractsDir = $domainDir.DIRECTORY_SEPARATOR.'Contracts';
                if (! is_dir($contractsDir)) {
                    continue;
                }

                $allBindings += $this->scanDirectory(
                    path: $contractsDir,
                    patterns: $patterns,
                    ignoredNamespaces: $ignoredNamespaces,
                );
            }
        }

        foreach ($allBindings as $abstract => $concrete) {
            $this->app->{$method}($abstract, $concrete);
        }
    }

    /**
     * Recursively scan a directory for interfaces and resolve to concrete classes.
     *
     * @param array<int, string> $patterns
     * @param array<int, string> $ignoredNamespaces
     *
     * @return array<string, string>
     */
    protected function scanDirectory(string $path, array $patterns, array $ignoredNamespaces): array
    {
        $bindings = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $interfaceInfo = $this->extractInterfaceInfo($file->getPathname());
            if ($interfaceInfo === null) {
                continue;
            }

            [$abstract, $interfaceName] = $interfaceInfo;

            if (Str::startsWith($abstract, $ignoredNamespaces)) {
                continue;
            }

            $concrete = $this->resolveConcrete($abstract, $interfaceName, $patterns);
            if ($concrete !== null) {
                $bindings[$abstract] = $concrete;
            }
        }

        return $bindings;
    }

    /**
     * Extract interface FQCN and short name from a PHP file.
     *
     * @return array{0: string, 1: string}|null
     */
    protected function extractInterfaceInfo(string $filePath): ?array
    {
        try {
            $content = File::get($filePath);

            if (! preg_match('/namespace\s+([^;]+);/', $content, $nsMatches)) {
                return null;
            }
            if (! preg_match('/interface\s+(\w+)/', $content, $ifMatches)) {
                return null;
            }

            $abstract = trim($nsMatches[1]).'\\'.trim($ifMatches[1]);

            if (! interface_exists($abstract)) {
                return null;
            }

            return [$abstract, trim($ifMatches[1])];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolve an interface to a concrete class using the first matching pattern.
     *
     * Supports both namespace layouts:
     *   - App\Contracts\{Domain}\XxxInterface → domain = App\{Domain}
     *   - App\{Domain}\Contracts\XxxInterface → domain = App\{Domain}
     *
     * @param array<int, string> $patterns
     */
    protected function resolveConcrete(string $abstract, string $interfaceName, array $patterns): ?string
    {
        $baseName = Str::replaceLast('Interface', '', Str::replaceLast('Contract', '', $interfaceName));
        $domainNamespace = $this->extractDomainNamespace($abstract);

        foreach ($patterns as $pattern) {
            $candidate = str_replace(
                ['{{domain}}', '{{base}}', '{{name}}'],
                [$domainNamespace, $baseName, $interfaceName],
                $pattern,
            );

            if (class_exists($candidate) && ! interface_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Extract the domain namespace from an interface FQCN.
     *
     * Handles both layouts:
     *   App\Services\Contracts\PaymentServiceInterface → App\Services
     *   App\Contracts\Services\PaymentServiceInterface → App\Services
     */
    protected function extractDomainNamespace(string $abstract): string
    {
        // Layout: App\Domain\{Domain}\Contracts\XxxInterface
        if (preg_match('/^(App\\\\Domain\\\\[^\\\\]+)\\\\Contracts\\\\/', $abstract, $matches)) {
            return $matches[1];
        }

        // Layout: App\{Domain}\Contracts\XxxInterface (legacy/fallback)
        if (preg_match('/^(App\\\\[^\\\\]+)\\\\Contracts\\\\/', $abstract, $matches)) {
            return $matches[1];
        }

        // Layout: App\Contracts\{Domain}\XxxInterface
        if (preg_match('/^App\\\\Contracts\\\\([^\\\\]+)/', $abstract, $matches)) {
            return 'App\\'.$matches[1];
        }

        return 'App';
    }

    /**
     * Register contextual (When-Needs-Give) bindings from config.
     */
    protected function registerContextualBindings(): void
    {
        foreach (config('bindings.contextual', []) as $binding) {
            if (isset($binding['when'], $binding['needs'], $binding['give'])) {
                $this->app
                    ->when($binding['when'])
                    ->needs($binding['needs'])
                    ->give($binding['give']);
            }
        }
    }
}
