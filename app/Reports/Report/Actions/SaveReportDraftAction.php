<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Core\Actions\BaseAction;
use App\Reports\Report\Models\Report;

final class SaveReportDraftAction extends BaseAction
{
    public function execute(Report $report, array $content): Report
    {
        return $this->transaction(function () use ($report, $content) {
            $report->update(['content' => $content]);

            $this->log('report_draft_saved', $report);

            return $report;
        });
    }
}
