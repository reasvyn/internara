
declare(strict_types=1);

namespace App\Domain\Document\Jobs;

use App\Jobs\BaseJob;
use App\Domain\Document\Models\GeneratedReport;
use App\Notifications\JobFailedNotification;
use App\Notifications\ReportGeneratedNotification;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportJob extends BaseJob
{
    public function __construct(public readonly string $reportId) {}

    public function handle(): void
    {
        $report = GeneratedReport::find($this->reportId);

        if (! $report) {
            return;
        }

        // Simulating heavy process. Exceptions thrown here will trigger
        // BaseJob retry mechanisms automatically.
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

        if ($report->user) {
            $report->user->notify(
                new ReportGeneratedNotification($report->report_type, (string) $report->id),
            );
        }
    }

    protected function generateContent(GeneratedReport $report): string
    {
        $filters = json_encode($report->filters ?? []);

        return "Report: {$report->report_type}\nGenerated: ".
            now()->format('Y-m-d H:i:s').
            "\nFilters: {$filters}\n\n[Report content would be generated here in production]";
    }

    /**
     * Handle job failure (called after max retries exhausted).
     */
    protected function onFailure(Throwable $exception): void
    {
        $report = GeneratedReport::find($this->reportId);

        if ($report) {
            $report->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            if ($report->user) {
                $report->user->notify(
                    new JobFailedNotification(
                        'Generate '.
                            ucwords(str_replace('_', ' ', $report->report_type)).
                            ' Report',
                        $exception->getMessage(),
                        '/admin/reports',
                    ),
                );
            }
        }
    }
}
