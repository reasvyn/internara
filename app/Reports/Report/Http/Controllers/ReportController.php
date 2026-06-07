<?php

declare(strict_types=1);

namespace App\Reports\Report\Http\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Document\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends BaseController
{
    public function download(Request $request, string $report): StreamedResponse|RedirectResponse
    {
        $document = Document::findOrFail($report);

        Gate::authorize('view', $document);

        $mediaUrl = $document->getFirstMediaUrl('file');

        if ($mediaUrl) {
            return redirect()->away($mediaUrl);
        }

        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            return Storage::disk('local')->download($document->file_path, $document->download_name);
        }

        if ($document->file_path) {
            return redirect()
                ->route('sysadmin.reports.index')
                ->with('error', 'Report file not found on disk.');
        }

        return redirect()->route('sysadmin.reports.index')->with('error', 'Report file not found.');
    }
}
