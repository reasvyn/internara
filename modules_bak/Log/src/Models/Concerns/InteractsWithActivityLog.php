<?php

declare(strict_types=1);

namespace Modules\Log\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Standardized concern for domain-critical entities to track their changes.
 *
 * This trait automatically records CRUD operations into the 'activity_log'
 * table, ensuring a consistent forensic audit trail for all aggregates.
 */
trait InteractsWithActivityLog
{
    use LogsActivity;

    /**
     * Define the options for the activity log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(static::class);
    }
}
