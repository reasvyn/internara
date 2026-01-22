<?php

declare(strict_types=1);

namespace Modules\Report\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Report\Services\Contracts\ReportGenerator;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $providerIdentifier,
        protected array $filters,
        protected string $jobId,
    ) {}

    public function handle(ReportGenerator $generator): void
    {
        $generator->generate($this->providerIdentifier, $this->filters);

        // In a real implementation, we would notify the user or update a job status record
    }
}
