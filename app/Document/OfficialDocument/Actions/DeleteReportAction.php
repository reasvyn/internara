<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseAction;
use App\Document\Models\Document;

final class DeleteReportAction extends BaseAction
{
    public function execute(Document $report): void
    {
        $report->delete();
    }
}
