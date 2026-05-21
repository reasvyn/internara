<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('accounts:auto-inactivate', ['--days' => 90])
    ->daily()
    ->description('Auto-inactivate accounts inactive for 90+ days');

Schedule::command('system:cleanup')
    ->daily()
    ->description('Prune expired records, stale cache, old activity logs');

Schedule::command('system:cache-warm')
    ->hourly()
    ->description('Pre-warm settings, brand, config, and view caches');

Schedule::command('pulse:check')
    ->everyMinute()
    ->description('Record Pulse performance metrics');

Schedule::command('queue:prune-failed')
    ->daily()
    ->description('Remove stale failed job records');
