<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function download(Request $request, string $report): StreamedResponse|RedirectResponse
    {
        $document = Document::findOrFail($report);

        Gate::authorize('view', $document);

        if (! Storage::disk('local')->exists($document->file_path)) {
            return redirect()
                ->route('admin.reports.index')
                ->with('error', 'Report file not found.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name ?? $document->name.'.pdf',
        );
    }
}
