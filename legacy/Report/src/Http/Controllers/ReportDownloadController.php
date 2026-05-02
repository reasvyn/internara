<?php

declare(strict_types=1);

namespace Modules\Report\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Report\Models\GeneratedReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ReportDownloadController
 *
 * Handles secure report file downloads.
 */
class ReportDownloadController extends Controller
{
    /**
     * Download a generated report.
     *
     * @param GeneratedReport $report The report record.
     */
    public function download(GeneratedReport $report): StreamedResponse
    {
        // Policy Check
        $this->authorize('view', $report);

        if (! Storage::disk('local')->exists($report->file_path)) {
            abort(404, __('report::messages.file_not_found'));
        }

        return Storage::disk('local')->download($report->file_path);
    }
}
