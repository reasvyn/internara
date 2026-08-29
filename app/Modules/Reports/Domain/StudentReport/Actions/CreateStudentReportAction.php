<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Reports\Domain\StudentReport\Data\CreateReportData;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;

final class CreateStudentReportAction extends BaseCommandAction
{
    public function execute(CreateReportData $data): Report
    {
        return $this->transaction(function () use ($data) {
            $report = Report::create([
                'registration_id' => $data->registrationId,
            ]);

            $this->log('report_created', $report);

            return $report;
        });
    }
}
