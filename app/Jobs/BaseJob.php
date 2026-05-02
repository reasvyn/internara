<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base abstract class for all queued jobs.
 * Provides standard configurations for retry, timeout, and centralized failure handling.
 *
 * S2 - Sustain: Consistent async infrastructure.
 * S3 - Scalable: Shared retry/backoff properties.
 */
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // Retry after 10s, then 30s, then 60s.
    }

    /**
     * Handle a job failure centrally.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Background Job Failed: '.get_class($this), [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'job_id' => $this->job ? $this->job->getJobId() : null,
        ]);

        $this->onFailure($exception);
    }

    /**
     * Child classes can override this to implement custom failure handling (e.g., notifications).
     */
    protected function onFailure(Throwable $exception): void
    {
        // Default: do nothing extra
    }
}
