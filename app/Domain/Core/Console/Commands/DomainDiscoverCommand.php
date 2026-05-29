<?php

declare(strict_types=1);

namespace App\Domain\Core\Console\Commands;

use App\Providers\DomainServiceProvider;
use Illuminate\Console\Command;

class DomainDiscoverCommand extends Command
{
    protected $signature = 'domain:discover';

    protected $description = 'Rediscover and register domain components (Livewire, policies, views)';

    public function handle(): int
    {
        $provider = app()->getProvider(DomainServiceProvider::class);

        $this->components->task(
            __('setup.cli.tasks.discover_livewire'),
            fn () => $provider->discoverLivewireComponents(),
        );

        $this->components->task(
            __('setup.cli.tasks.discover_policies'),
            fn () => $provider->discoverPolicies(),
        );

        $this->components->task(
            __('setup.cli.tasks.discover_views'),
            fn () => $provider->registerBladeNamespaces(),
        );

        $this->newLine();
        $this->components->info(__('setup.cli.tasks.discover_complete'));

        return self::SUCCESS;
    }
}
