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
        protected ?string $userId = null,
    ) {}

    public function handle(ReportGenerator $generator): void
    {
        $filePath = $generator->generate($this->providerIdentifier, $this->filters, $this->userId);

        if ($this->userId) {
            $user = \Modules\User\Models\User::find($this->userId);
            if ($user) {
                $provider = $generator->getProviders()->get($this->providerIdentifier);
                $title = $provider ? $provider->getLabel() : 'Report';

                app(\Modules\Notification\Services\Contracts\NotificationService::class)->send(
                    $user,
                    new \Modules\Report\Notifications\ReportGeneratedNotification($title, $filePath)
                );
            }
        }
    }
}
