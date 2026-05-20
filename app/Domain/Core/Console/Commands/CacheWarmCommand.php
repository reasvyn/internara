<?php

declare(strict_types=1);

namespace App\Domain\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CacheWarmCommand extends Command
{
    protected $signature = 'system:cache-warm
        {--domains=* : Specific domain caches to warm (e.g. admin, school)}';

    protected $description = 'Pre-warm application caches for faster first requests';

    public function handle(): int
    {
        $this->info('Starting cache warm...');

        $this->warmSettings();
        $this->warmBrand();
        $this->warmConfig();
        $this->warmViews();
        $this->warmEvents();

        $this->info("\nServer is ready — all caches warmed.");

        return Command::SUCCESS;
    }

    protected function warmSettings(): void
    {
        $this->info('  → Warming settings cache...');
        setting('app_name', skipCache: false);
        setting('primary_color', skipCache: false);
    }

    protected function warmBrand(): void
    {
        $this->info('  → Warming brand cache...');
        brand('name');
        brand('colors');
    }

    protected function warmConfig(): void
    {
        $this->info('  → Caching config...');
        Artisan::call('config:cache');
    }

    protected function warmViews(): void
    {
        $this->info('  → Caching views...');
        Artisan::call('view:cache');
    }

    protected function warmEvents(): void
    {
        $this->info('  → Caching events...');
        Artisan::call('event:cache');
    }
}
