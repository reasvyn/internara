<?php

declare(strict_types=1);

namespace App\Actions\Document;

use App\Models\Document;

class DeleteReportAction
{
    public function execute(Document $report): void
    {
        $report->delete();
    }
}
