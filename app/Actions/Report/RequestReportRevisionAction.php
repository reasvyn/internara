<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Actions\Core\LogAuditAction;
use App\Models\Report;
use App\Models\ReportRevision;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RequestReportRevisionAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Report $report, string $feedback): Report
    {
        if ($report->status->isTerminal()) {
            throw new RuntimeException('This report has already been approved.');
        }

        return DB::transaction(function () use ($report, $feedback) {
            $latestRound = $report->revisions()->max('round') ?? 0;

            ReportRevision::create([
                'report_id' => $report->id,
                'round' => $latestRound + 1,
                'feedback' => $feedback,
                'requested_by' => auth()->id(),
                'requested_at' => now(),
            ]);

            $report->update(['status' => 'revision_required']);

            $this->logAudit->execute(
                action: 'report_revision_requested',
                subjectType: Report::class,
                subjectId: $report->id,
                payload: ['round' => $latestRound + 1],
                module: 'Report',
            );

            return $report->fresh();
        });
    }
}
