<?php

declare(strict_types=1);

namespace App\Actions\Report;

use App\Actions\Core\LogAuditAction;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveReportAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Report $report, array $data): Report
    {
        if ($report->status->isTerminal()) {
            throw new RuntimeException('This report has already been approved.');
        }

        return DB::transaction(function () use ($report, $data) {
            $report->update([
                'status' => 'approved',
                'score' => $data['score'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            $this->logAudit->execute(
                action: 'report_approved',
                subjectType: Report::class,
                subjectId: $report->id,
                payload: ['score' => $data['score'] ?? null],
                module: 'Report',
            );

            return $report->fresh();
        });
    }
}
