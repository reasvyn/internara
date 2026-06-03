<?php

declare(strict_types=1);

namespace App\Domain\Certification\Aggregates\Document\Actions;

use App\Domain\Certification\Aggregates\Document\Models\Document;
use App\Domain\Core\Actions\BaseAction;

final class DeleteReportAction extends BaseAction
{
    public function execute(Document $report): void
    {
        $report->delete();
    }
}
