<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Reports\Report\Data\CreateReportData;
use App\Reports\Report\Models\Report;

final class CreateReportAction extends BaseCommandAction
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
