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
     * Persists a formal performance evaluation for a student registration.
     *
     * This method acts as the technical bridge between supervisory feedback
     * and institutional outcomes, applying rubric-based scoring to certify
     * vocational achievement.
     *
     * @param string $registrationId Authoritative registration UUID.
     * @param string $evaluatorId Authenticated subject identifier.
     * @param string $type Evaluation category (e.g., mentor, instructor).
     * @param array $data Rubric-based marks and scores.
     * @param string|null $feedback Qualitative pedagogical comments.
     */
    public function submitEvaluation(
        string $registrationId,
        string $evaluatorId,
        string $type,
        array $data,
        ?string $feedback = null,
    ): Assessment;

    /**
     * Synthesizes a comprehensive score card for a student registration.
     *
     * Aggregates manual evaluations and automated compliance scores to
     * provide a unified view of the student's performance journey.
     */
    public function getScoreCard(string $registrationId): array;

    /**
     * Calculates the mean evaluation performance across a set of registrations.
     *
     * Facilitates institutional analytics by aggregating scoring trends
     * for specific cohorts or departments.
     *
     * @return array<string, float> Map of registration ID to average score.
     */
    public function getAverageScore(array $registrationIds, string $type = 'mentor'): array;

    /**
     * Assesses the student's eligibility for program finalization.
     *
     * Verification: Implementation MUST audit all mandatory evaluation
     * criteria to ensure that the student is academically ready for
     * graduation/certification.
     *
     * @return array{is_ready: bool, missing: array<string>}
     */
    public function getReadinessStatus(string $registrationId): array;
}
