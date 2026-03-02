<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Assessment\Services\Contracts\AnalyticsService as Contract;
use Modules\Assessment\Services\Contracts\CompetencyService;
use Modules\Assessment\Services\Contracts\ComplianceService;
use Modules\Shared\Services\BaseService;

class AnalyticsService extends BaseService implements Contract
{
    public function __construct(
        protected CompetencyService $competencyService,
        protected ComplianceService $complianceService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getCompetencyStats(string $registrationId): array
    {
        return Cache::remember(
            "assessment:analytics:competency:{$registrationId}",
            now()->addHours(1),
            fn () => $this->competencyService->getProgressStats($registrationId),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParticipationTrends(string $registrationId): array
    {
        return Cache::remember(
            "assessment:analytics:participation:{$registrationId}",
            now()->addHours(1),
            fn () => $this->complianceService->calculateScore($registrationId),
        );
    }
}
