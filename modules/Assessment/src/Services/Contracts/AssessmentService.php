<?php

declare(strict_types=1);

namespace Modules\Assessment\Services\Contracts;

use Modules\Assessment\Models\Assessment;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<Assessment>
 */
interface AssessmentService extends EloquentQuery
{
    /**
     * Submit an evaluation for an internship registration.
     */
    public function submitEvaluation(
        string $registrationId,
        string $evaluatorId,
        string $type,
        array $data,
        ?string $feedback = null,
    ): Assessment;

    /**
     * Get the aggregated score card for a registration.
     */
    public function getScoreCard(string $registrationId): array;

    /**
     * Get the average score for a set of registrations.
     *
     * @param array<string> $registrationIds
     * @param string $type The evaluator type (mentor, teacher)
     */
    public function getAverageScore(array $registrationIds, string $type = 'mentor'): float;
}
