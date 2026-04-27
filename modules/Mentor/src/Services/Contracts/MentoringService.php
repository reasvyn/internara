<?php

declare(strict_types=1);

namespace Modules\Mentor\Services\Contracts;

use Illuminate\Support\Collection;
use Modules\Mentor\Models\MentoringLog;
use Modules\Mentor\Models\MentoringVisit;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Interface MentoringService
 *
 * Handles management of mentoring visits and logs.
 */
interface MentoringService extends EloquentQuery
{
    /**
     * Record a mentoring visit.
     */
    public function recordVisit(array $data): MentoringVisit;

    /**
     * Record a mentoring log/feedback.
     */
    public function recordLog(array $data): MentoringLog;

    /**
     * Get mentoring stats for a registration.
     */
    public function getMentoringStats(string $registrationId): array;

    /**
     * Get a combined chronological timeline of visits and logs.
     */
    public function getUnifiedTimeline(string $registrationId): Collection;
}
