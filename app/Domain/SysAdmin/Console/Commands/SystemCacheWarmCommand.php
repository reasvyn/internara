<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Console\Commands;

use App\Domain\Core\Support\SmartLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SystemCacheWarmCommand extends Command
{
    protected $signature = 'system:cache-warm';

    protected $description = 'Pre-warm application caches for faster first requests';

    public function handle(): int
    {
        try {
            $this->info(__('setup.system.cache_warm_starting'));

            $this->warmSettings();
            $this->warmBrand();
            $this->warmConfig();
            $this->warmViews();
            $this->warmEvents();

            $this->newLine();
            $this->components->info(__('setup.system.cache_warm_completed'));

            SmartLogger::info(__('setup.system.cache_warm_completed'))
                ->module('system')
                ->event('cache.warm.completed')
                ->save();

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('Cache warm failed')
                ->module('system')
                ->event('cache.warm.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->save();

            $this->error(__('setup.system.cache_warm_starting').': '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function warmSettings(): void
    {
        $this->components->task(
            __('setup.system.cache_warm_settings'),
            function () {
                setting('app_name', skipCache: false);
                setting('primary_color', skipCache: false);

                return true;
            },
        );
    }

    protected function warmBrand(): void
    {
        $this->components->task(
            __('setup.system.cache_warm_brand'),
            function () {
                brand('name');
                brand('colors');

                return true;
            },
        );
    }

    protected function warmConfig(): void
    {
        $this->components->task(
            __('setup.system.cache_warm_config'),
            function () {
                Artisan::call('config:cache');

                return true;
            },
        );
    }

    protected function warmViews(): void
    {
        $this->components->task(
            __('setup.system.cache_warm_views'),
            function () {
                Artisan::call('view:cache');

                return true;
            },
        );
    }

    protected function warmEvents(): void
    {
        $this->components->task(
            __('setup.system.cache_warm_events'),
            function () {
                Artisan::call('event:cache');

                return true;
            },
        );
    }
}
