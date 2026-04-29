<?php

declare(strict_types=1);

namespace Modules\Status\Observers;

use Illuminate\Support\Str;
use Modules\Status\Models\Status;

class StatusObserver
{
    /**
     * Handle the Status "creating" event.
     */
    public function creating(Status $status): void
    {
        if (empty($status->{$status->getKeyName()})) {
            $status->{$status->getKeyName()} = (string) Str::uuid();
        }
    }
}
