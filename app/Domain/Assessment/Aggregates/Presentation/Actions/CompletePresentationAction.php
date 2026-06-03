<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Aggregates\Presentation\Actions;

use App\Domain\Assessment\Aggregates\Presentation\Models\Presentation;
use App\Domain\Core\Actions\BaseAction;

final class CompletePresentationAction extends BaseAction
{
    public function execute(Presentation $presentation, int $reportWeight = 50, int $presentationWeight = 50): Presentation
    {
        return $this->transaction(function () use ($presentation, $reportWeight, $presentationWeight) {
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

            $this->log('presentation_completed', $presentation, ['final_score' => $presentation->final_score]);

            return $presentation->fresh();
        });
    }
}
