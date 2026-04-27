<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Status\Services\Jobs\DetectIdleAccountsJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
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
        $schedule
            ->job(new DetectIdleAccountsJob())
            ->daily()
            ->onSuccess(function () {
                Log::info('Idle account detection completed successfully');
            })
            ->onFailure(function () {
                Log::error('Idle account detection job failed - check logs');
            });

        /**
         * Cleanup expired activation tokens
         * Remove tokens older than 24 hours + expired
         */
        $schedule
            ->call(function () {
                DB::table('activation_tokens')->where('expires_at', '<', now())->delete();
            })
            ->daily()
            ->name('cleanup-expired-tokens');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
