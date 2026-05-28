<?php

declare(strict_types=1);

namespace App\Domain\Document\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Document\Models\Document;

final class DeleteReportAction extends BaseAction
{
    public function execute(Document $report): void
    {
        $report->delete();
    }
}
