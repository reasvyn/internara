<?php

declare(strict_types=1);

namespace Modules\Log\Services;

use Modules\Log\Models\Activity;
use Modules\Log\Services\Contracts\ActivityService as Contract;
use Modules\Shared\Services\EloquentQuery;

class ActivityService extends EloquentQuery implements Contract
{
    public function __construct(Activity $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getEngagementStats(array $registrationIds): array
    {
        // For now, return basic stats based on activity logs
        $count = $this->query([
            'subject_type' => 'Modules\Internship\Models\InternshipRegistration',
        ])
            ->whereIn('subject_id', $registrationIds)
            ->count();

        return [
            'activity_count' => $count,
            'last_activity' => $this->query()->latest()->first()?->created_at,
        ];
    }
}
