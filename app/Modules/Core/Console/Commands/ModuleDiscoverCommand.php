<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Services\ModuleService;
use App\Modules\Core\Services\SmartLogger;
use Illuminate\Console\Command;
use RuntimeException;

class ModuleDiscoverCommand extends Command
{
    public function __construct(
        private readonly ModuleService $service,
    ) {
        parent::__construct();
    }

    protected $signature = 'module:discover';

    protected $description = 'Rediscover and register module components (Livewire, policies, views)';

    public function handle(): int
    {
        try {
            $providers = $this->getLaravel()->getLoadedProviders();

            if (! isset($providers['App\Providers\AppServiceProvider']) || ! $providers['App\Providers\AppServiceProvider']) {
                throw new RuntimeException('AppServiceProvider is not registered.');
            }

            $this->components->task(
                __('setup.cli.tasks.discover_livewire'),
                fn () => $this->service->discoverLivewireComponents(),
            );

            $this->components->task(
                __('setup.cli.tasks.discover_policies'),
                fn () => $this->service->discoverPolicies(),
            );

            $this->components->task(
                __('setup.cli.tasks.discover_views'),
                fn () => $this->service->registerBladeNamespaces(),
            );

            $this->newLine();
            $this->components->info(__('setup.cli.tasks.discover_complete'));

            SmartLogger::info(__('setup.cli.tasks.discover_complete'))
                ->module('setup')
                ->event('module.discover.completed')
                ->withPiiMasking()
                ->save();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('Module discovery failed')
                ->module('setup')
                ->event('module.discover.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->withPiiMasking()
                ->save();

            $this->error(__('setup.cli.tasks.discover_failed').': '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
