<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Http\Controllers;

use App\Modules\Core\Http\Controllers\BaseController;
use App\Modules\Reports\Domain\StudentReport\Actions\DownloadStudentReportAction;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentReportController extends BaseController
{
    public function __construct(
        private readonly DownloadStudentReportAction $downloadReportAction,
    ) {}

    public function download(Request $request, StudentReport $report): StreamedResponse|BinaryFileResponse
    {
        return $this->downloadReportAction->execute($report);
    }
}
