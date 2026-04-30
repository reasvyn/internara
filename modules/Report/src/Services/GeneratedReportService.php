<?php

declare(strict_types=1);

namespace Modules\Report\Services;

use Modules\Report\Models\GeneratedReport;
use Modules\Shared\Services\EloquentQuery;

class GeneratedReportService extends EloquentQuery implements Contracts\GeneratedReportService
{
    public function __construct(GeneratedReport $model)
    {
        $this->setModel($model);
    }
}
