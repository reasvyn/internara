<?php

declare(strict_types=1);

namespace App\Reports\Report\Http\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Reports\Report\Actions\DownloadReportAction;
use App\Reports\Report\Models\Report;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends BaseController
{
    public function __construct(
        private readonly DownloadReportAction $downloadReportAction,
    ) {}

    public function download(Request $request, Report $report): StreamedResponse|BinaryFileResponse
    {
        return $this->downloadReportAction->execute($report);
    }
}
