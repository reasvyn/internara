<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\OfficialDocument\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Document\Models\Document;

final class DeleteReportAction extends BaseCommandAction
{
    public function execute(Document $report): void
    {
        $this->transaction(function () use ($report) {
            $report->delete();

            $this->log('report_deleted', $report, ['title' => $report->title]);
        });
    }
}
