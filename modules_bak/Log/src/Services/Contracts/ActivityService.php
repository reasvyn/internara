<?php

declare(strict_types=1);

namespace Modules\Log\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Interface ActivityService
 *
 * Defines the contract for managing and querying system activity logs.
 */
interface ActivityService extends EloquentQuery
{
    /**
     * Get engagement statistics for specific registrations.
     */
    public function getEngagementStats(array $registrationIds): array;
}
