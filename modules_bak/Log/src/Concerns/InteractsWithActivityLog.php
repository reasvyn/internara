<?php

declare(strict_types=1);

namespace Modules\Log\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait InteractsWithActivityLog
 *
 * Provides a standardized way to log user interactions using Spatie Activitylog.
 */
trait InteractsWithActivityLog
{
    use LogsActivity;

    /**
     * Get the options for logging activity.
     */
    public function getActivitylogOptions(): LogOptions
    {
        $logName = property_exists($this, 'activityLogName') ? $this->activityLogName : 'default';

        return LogOptions::defaults()
            ->useLogName($logName)
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
