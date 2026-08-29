<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Actions;

use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Document\Models\Document;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadStudentReportAction extends BaseProcessAction
{
    public function execute(Report $report): StreamedResponse|BinaryFileResponse
    {
        return $this->step('download_report', function () use ($report) {
            $document = $this->resolveDocument($report);

            Gate::authorize('view', $document);

            $mediaUrl = $document->getFirstMediaUrl('file');

            if ($mediaUrl) {
                return redirect()->away($mediaUrl);
            }

            if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
                return Storage::disk('local')->download($document->file_path, $document->download_name);
            }

            abort(404, __('report.file_not_found'));
        });
    }

    private function resolveDocument(Report $report): Document
    {
        return Document::findOrFail($report->id);
    }

    protected function moduleName(): string
    {
        return 'Reports';
    }
}
