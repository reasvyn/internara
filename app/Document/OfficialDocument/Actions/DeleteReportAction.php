<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Document\Models\Document;

final class DeleteReportAction extends BaseCommandAction
{
    public function execute(Document $report): void
    {
        $report->delete();
    }
}
