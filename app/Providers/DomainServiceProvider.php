<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Admin\Actions\SendNotificationAction;
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
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class DomainServiceProvider extends ServiceProvider
{
    private const DOMAIN_PATH = __DIR__.'/../Domain';

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

        // Cross-domain event listeners
        Event::listen(
            SetupFinalized::class,
            [LogSetupFinalized::class, 'handle'],
        );

        // Cross-domain policy registrations (auto-discovery handles domain patterns)
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
     * Register Blade anonymous component paths and view namespaces for each domain.
     *
     * - Anonymous components: `x-{domain}::component-name` via Blade::anonymousComponentPath
     * - View namespaces: `{domain}::view.name` via View::addNamespace (required by Livewire
     *   #[Layout] attribute and explicit namespace-based includes)
     */
    private function registerBladeNamespaces(): void
    {
        $viewsDir = realpath(config('domain.paths.views', self::DOMAIN_PATH.'/../../resources/views'));

        if ($viewsDir === false) {
            return;
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

            if (is_dir($dir)) {
                Blade::anonymousComponentPath($dir, $name);
                View::addNamespace($name, $dir);
            }
        }
    }
}
