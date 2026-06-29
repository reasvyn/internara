<?php

declare(strict_types=1);

namespace App\Reports\Report\Listeners;

use App\Core\Contracts\SendsNotifications;
use App\Reports\Report\Events\ReportApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

final class HandleReportApproved implements ShouldQueue
{
    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(ReportApproved $event): void
    {
        $report = $event->report;

        $this->sendNotification->execute(
            userId: $report->registration?->student_id,
            type: 'report_approved',
            title: __('notifications.report_approved.title'),
            message: __('notifications.report_approved.message', ['title' => $report->title]),
            link: route('reports.show', $report),
        );
    }
}
