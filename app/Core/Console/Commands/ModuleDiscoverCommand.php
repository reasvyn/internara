<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Support\SmartLogger;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Command;

class ModuleDiscoverCommand extends Command
{
    protected $signature = 'module:discover';

    protected $description = 'Rediscover and register module components (Livewire, policies, views)';

    public function handle(): int
    {
        try {
            $provider = app()->getProvider(AppServiceProvider::class);

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

            SmartLogger::info(__('setup.cli.tasks.discover_complete'))
                ->module('setup')
                ->event('domain.discover.completed')
                ->save();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('Module discovery failed')
                ->module('setup')
                ->event('domain.discover.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->save();

            $this->error(__('setup.cli.tasks.discover_failed').': '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
