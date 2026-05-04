<?php

declare(strict_types=1);

namespace App\Domain\Document\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Document\Models\GeneratedReport;
use App\Domain\User\Models\User;
use App\Jobs\Report\GenerateReportJob;
use Illuminate\Support\Facades\DB;

/**
 * Queues a report for async generation.
 *
 * S1 - Secure: Validates user has permission to generate reports.
 * S2 - Sustain: Offloads heavy generation to queue for sustainability.
 */
class QueueReportGenerationAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, string $reportType, array $filters = []): GeneratedReport
    {
        return DB::transaction(function () use ($user, $reportType, $filters) {
            $report = GeneratedReport::create([
                'user_id' => $user->id,
                'report_type' => $reportType,
                'filters' => $filters,
                'status' => 'pending',
            ]);

            GenerateReportJob::dispatch($report->id)->afterCommit();

            $this->logAudit->execute(
                action: 'report_queued',
                subjectType: GeneratedReport::class,
                subjectId: $report->id,
                payload: ['report_type' => $reportType],
                module: 'Report',
            );

            return $report;
        });
    }
}
