<?php

declare(strict_types=1);

namespace App\Actions\Presentation;

use App\Actions\Core\LogAuditAction;
use App\Models\Presentation;
use Illuminate\Support\Facades\DB;

class CompletePresentationAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Presentation $presentation, int $reportWeight = 50, int $presentationWeight = 50): Presentation
    {
        return DB::transaction(function () use ($presentation, $reportWeight, $presentationWeight) {
            $avgScore = $presentation->examiners()
                ->whereNotNull('score')
                ->avg('score');

            $presentation->update([
                'status' => 'completed',
                'completed_at' => now(),
                'presentation_score' => $avgScore,
            ]);

            $presentation->refresh();

            $report = $presentation->registration->report;

            if ($report && $report->score !== null) {
                $total = ($presentation->presentation_score * $presentationWeight / 100)
                    + ($report->score * $reportWeight / 100);
                $presentation->update(['final_score' => round($total, 2)]);
            }

            $this->logAudit->execute(
                action: 'presentation_completed',
                subjectType: Presentation::class,
                subjectId: $presentation->id,
                payload: ['final_score' => $presentation->final_score],
                module: 'Presentation',
            );

            return $presentation->fresh();
        });
    }
}
