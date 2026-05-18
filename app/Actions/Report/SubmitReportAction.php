<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Actions\Core\LogAuditAction;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmitReportAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Report $report, array $content): Report
    {
        if ($report->status->isTerminal()) {
            throw new RuntimeException('This report has already been approved.');
        }

        return DB::transaction(function () use ($report, $content) {
            $report->update([
                'content' => $content,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $this->logAudit->execute(
                action: 'report_submitted',
                subjectType: Report::class,
                subjectId: $report->id,
                payload: ['title' => $report->title],
                module: 'Report',
            );

            return $report->fresh();
        });
    }
}
