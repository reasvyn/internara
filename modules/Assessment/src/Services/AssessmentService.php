<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Services\Contracts\AssessmentService as Contract;
use Modules\Assessment\Services\Contracts\ComplianceService;
use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Shared\Services\EloquentQuery;

class AssessmentService extends EloquentQuery implements Contract
{
    public function __construct(
        protected ComplianceService $complianceService,
        protected RegistrationService $registrationService,
        Assessment $model,
    ) {
        $this->setModel($model);
        $this->setSearchable(['type', 'academic_year']);
        $this->setSortable(['created_at', 'score']);
    }

    /**
     * {@inheritdoc}
     */
    public function submitEvaluation(
        string $registrationId,
        string $evaluatorId,
        string $type,
        array $data,
        ?string $feedback = null,
    ): Assessment {
        // Authorization: Verify evaluator is assigned to this registration
        $registration = $this->registrationService->find($registrationId);

        if (! $registration) {
            throw new AppException('assessment::messages.invalid_registration', code: 404);
        }

        $isAuthorized = match ($type) {
            'teacher' => $registration->teacher_id === $evaluatorId,
            'mentor' => $registration->mentor_id === $evaluatorId,
            default => false,
        };

        if (! $isAuthorized) {
            throw new AppException('assessment::messages.unauthorized', code: 403);
        }

        // Calculate average score
        $scores = array_filter($data, fn ($value) => is_numeric($value));
        $finalScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        return $this->save(
            [
                'registration_id' => $registrationId,
                'type' => $type,
            ],
            [
                'evaluator_id' => $evaluatorId,
                'content' => $data,
                'score' => $finalScore,
                'feedback' => $feedback,
                // Automatically finalize on submission for the 'Streamlined' workflow
                'finalized_at' => now(),
            ],
        );
    }

    public function getScoreCard(string $registrationId): array
    {
        $assessments = $this->model
            ->newQuery()
            ->select(['type', 'score', 'registration_id'])
            ->where('registration_id', $registrationId)
            ->get()
            ->keyBy('type');

        $compliance = $this->complianceService->calculateScore($registrationId);

        return [
            'mentor' => $assessments->get('mentor'),
            'teacher' => $assessments->get('teacher'),
            'compliance' => $compliance,
            'final_grade' => $this->calculateFinalGrade(
                $assessments,
                $compliance['final_score'] ?? 0,
            ),
        ];
    }

    protected function calculateFinalGrade($assessments, float $complianceScore): ?float
    {
        $mentor = $assessments->get('mentor');
        $teacher = $assessments->get('teacher');

        if (! $mentor || ! $teacher) {
            return null;
        }

        // Formula: Mentor (40%) + Teacher (40%) + Compliance (20%)
        return round($mentor->score * 0.4 + $teacher->score * 0.4 + $complianceScore * 0.2, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function getAverageScore(array $registrationIds, string $type = 'mentor'): array
    {
        if (empty($registrationIds)) {
            return [];
        }

        return $this->model
            ->newQuery()
            ->select('registration_id')
            ->selectRaw('AVG(score) as avg_score')
            ->whereIn('registration_id', $registrationIds)
            ->where('type', $type)
            ->groupBy('registration_id')
            ->get()
            ->pluck('avg_score', 'registration_id')
            ->map(fn($score) => (float) $score)
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getReadinessStatus(string $registrationId): array
    {
        $registration = $this->registrationService->find($registrationId);
        $missing = [];

        if (! $registration) {
            return ['is_ready' => false, 'missing' => ['Invalid registration']];
        }

        // 1. Check Period
        if (! $registration->end_date || $registration->end_date->isFuture()) {
            $missing[] = __('assessment::messages.period_not_ended');
        }

        // 2. Check Evaluations
        $assessments = $this->model
            ->newQuery()
            ->select(['type', 'registration_id'])
            ->where('registration_id', $registrationId)
            ->get();

        if (! $assessments->where('type', 'teacher')->first()) {
            $missing[] = __('assessment::messages.missing_teacher_eval');
        }
        if (! $assessments->where('type', 'mentor')->first()) {
            $missing[] = __('assessment::messages.missing_mentor_eval');
        }

        // 3. Check Mandatory Assignments
        $assignmentService = app(\Modules\Assignment\Services\Contracts\AssignmentService::class);
        if (! $assignmentService->isFulfillmentComplete($registrationId)) {
            $missing[] = __('assessment::messages.missing_assignments');
        }

        return [
            'is_ready' => empty($missing),
            'missing' => $missing,
        ];
    }
}
