<?php

declare(strict_types=1);

namespace App\Modules\Report\Domain\StudentReport\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Report\Domain\StudentReport\Data\CreateStudentReportData;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;

final class CreateStudentReportAction extends BaseCommandAction
{
    public function execute(CreateStudentReportData $data): StudentReport
    {
        return $this->transaction(function () use ($data) {
            $report = StudentReport::create([
                'registration_id' => $data->registrationId,
            ]);

            $this->log('report_created', $report);

            return $report;
        });
    }
}
