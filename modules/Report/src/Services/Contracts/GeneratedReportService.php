<?php

declare(strict_types=1);

namespace Modules\Report\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Report\Models\GeneratedReport
 * @extends EloquentQuery<TModel>
 */
interface GeneratedReportService extends EloquentQuery
{
    //
}
