<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Document\Actions\DownloadReportAction;
use App\Domain\Document\Actions\QueueReportGenerationAction;
use App\Domain\Document\Models\GeneratedReport;
use App\Http\Requests\GenerateReportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', GeneratedReport::class);

        $reports = $request->user()->generatedReports()->latest()->paginate(20);

        return view('livewire.admin.reports.index', [
            'reports' => $reports,
        ]);
    }

    public function store(GenerateReportRequest $request, QueueReportGenerationAction $action)
    {
        Gate::authorize('create', GeneratedReport::class);

        $report = $action->execute(
            $request->user(),
            $request->validated('report_type'),
            $request->validated('filters', []),
        );

        return redirect()
            ->route('admin.reports.index')
            ->with('success', 'Report generation has been queued.');
    }

    public function download(
        GeneratedReport $report,
        Request $request,
        DownloadReportAction $action,
    ): StreamedResponse {
        Gate::authorize('download', $report);

        $content = $action->execute($request->user(), $report);

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            basename($report->file_path),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
