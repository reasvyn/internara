<?php

declare(strict_types=1);

namespace App\Jobs\Report;

use App\Models\GeneratedReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $reportId
    ) {}

    public function handle(): void
    {
        $report = GeneratedReport::find($this->reportId);

        if (!$report) {
            return;
        }

        try {
            $content = $this->generateContent($report);
            $fileName = "{$report->report_type}_{$report->id}.pdf";
            $path = "reports/{$fileName}";

            Storage::disk('private')->put($path, $content);

            $report->update([
                'file_path' => $path,
                'file_size' => strlen($content),
                'status' => 'completed',
                'generated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected function generateContent(GeneratedReport $report): string
    {
        $filters = json_encode($report->filters ?? []);

        return "Report: {$report->report_type}\nGenerated: " . now()->format('Y-m-d H:i:s') . "\nFilters: {$filters}\n\n[Report content would be generated here in production]";
    }
}
