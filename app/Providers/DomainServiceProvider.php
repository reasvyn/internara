<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Policies\UserPolicy;
use App\Domain\Core\Contracts\SendsNotifications;
use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Internship\Policies\CompanyPolicy;
use App\Domain\Internship\Policies\InternshipRegistrationPolicy;
use App\Domain\Partnership\Models\Company;
use App\Domain\Placement\Models\Placement;
use App\Domain\Placement\Policies\InternshipPlacementPolicy;
use App\Domain\Registration\Models\Registration;
use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Listeners\LogSetupFinalized;
use App\Domain\User\Actions\SendNotificationAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class DomainServiceProvider extends ServiceProvider
{
    private const DOMAIN_PATH = __DIR__.'/../Domain';

    private const CACHE_KEY_POLICIES = 'domain.discovered_policies';

    private const CACHE_KEY_LIVEWIRE = 'domain.discovered_livewire';

    private const CACHE_KEY_VIEWS = 'domain.discovered_views';

    public function register(): void
    {
        $this->app->bind(SendsNotifications::class, SendNotificationAction::class);
    }

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

        Event::listen(
            SetupFinalized::class,
            [LogSetupFinalized::class, 'handle'],
        );

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Placement::class, InternshipPlacementPolicy::class);
        Gate::policy(Registration::class, InternshipRegistrationPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
    }

    protected function registerCommands(): void
    {
        if (! config('domain.commands.enabled', true)) {
            return;
        }

        $this->commands([
            __DIR__.'/../Domain/Core/Console/Commands',
            __DIR__.'/../Domain/Setup/Console/Commands',
            __DIR__.'/../Domain/Auth/Console/Commands',
            __DIR__.'/../Domain/Admin/Console/Commands',
            __DIR__.'/../Domain/User/Console/Commands',
        ]);
    }

    private function discoverLivewireComponents(): void
    {
        $cacheKey = self::CACHE_KEY_LIVEWIRE;
        $ttl = 86400;

        $components = Cache::remember($cacheKey, $ttl, function () {
            $result = [];
            $domainDir = realpath(self::DOMAIN_PATH);
            if ($domainDir === false) {
                return $result;
            }

            $directory = config('domain.livewire.directory', 'Livewire');
            $excludePaths = config('domain.livewire.exclude_paths', ['Concerns', 'Traits']);
            $files = $this->scanPhpFiles($domainDir);

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

                preg_match('#/Domain/([^/]+)/'.$directory.'/#', $filePath, $domainMatch);
                $domain = $domainMatch[1] ?? '';
                $alias = Str::kebab($domain).'.'.Str::kebab($className);

                $result[$alias] = $fqcn;
            }

            return $result;
        });

        foreach ($components as $alias => $fqcn) {
            Livewire::component($alias, $fqcn);
        }
    }

    private function discoverPolicies(): void
    {
        $cacheKey = self::CACHE_KEY_POLICIES;
        $ttl = 86400;

        $policies = Cache::remember($cacheKey, $ttl, function () {
            $result = [];
            $domainDir = realpath(self::DOMAIN_PATH);
            if ($domainDir === false) {
                return $result;
            }

            $directory = config('domain.policies.directory', 'Policies');
            $excludePaths = config('domain.policies.exclude_paths', ['Concerns', 'Traits']);
            $files = $this->scanPhpFiles($domainDir);

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

                preg_match('#/Domain/([^/]+)/'.$directory.'/#', $filePath, $domainMatch);
                $domain = $domainMatch[1] ?? '';
                $modelName = preg_replace('/Policy$/', '', $className);
                $modelClass = "App\\Domain\\{$domain}\\Models\\{$modelName}";

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

    private function registerBladeNamespaces(): void
    {
        $cacheKey = self::CACHE_KEY_VIEWS;
        $ttl = 86400;

        $namespaces = Cache::remember($cacheKey, $ttl, function () {
            $result = [];
            $viewsDir = realpath(config('domain.paths.views', self::DOMAIN_PATH.'/../../resources/views'));
            if ($viewsDir === false) {
                return $result;
            }

            $excluded = config('domain.views.exclude_directories', [
                'components', 'emails', 'errors', 'mcp', 'pdf', 'vendor',
            ]);

            $domainDirs = glob($viewsDir.'/*', GLOB_ONLYDIR);
            foreach ($domainDirs as $dir) {
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
