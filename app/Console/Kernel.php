<?php

declare(strict_types=1);

namespace App\Console;

use Modules\Status\Services\Jobs\DetectIdleAccountsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /**
         * Account Status Automation
         *
         * Daily job to detect and handle idle accounts:
         * - 180+ days → send warning (INACTIVE approaching)
         * - 365+ days → auto-archive (ARCHIVED)
         * - 7+ years → GDPR anonymization & purge
         */
        $schedule->job(new DetectIdleAccountsJob)
            ->daily()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Idle account detection completed successfully');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Idle account detection job failed - check logs');
            });

        /**
         * Cleanup expired activation tokens
         * Remove tokens older than 24 hours + expired
         */
        $schedule->call(function () {
            \Illuminate\Support\Facades\DB::table('activation_tokens')
                ->where('expires_at', '<', now())
                ->delete();
        })->daily()->name('cleanup-expired-tokens');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
