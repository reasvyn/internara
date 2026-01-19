<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Services\Contracts\AssessmentService as Contract;
use Modules\Exception\AppException;
use Modules\Shared\Services\EloquentQuery;

class AssessmentService extends EloquentQuery implements Contract
{
    public function __construct()
    {
        $this->setModel(new Assessment);
        $this->setSearchable(['type', 'academic_year']);
        $this->setSortable(['created_at', 'score']);
    }

    public function submitEvaluation(
        string $registrationId,
        string $evaluatorId,
        string $type,
        array $data,
        ?string $feedback = null,
    ): Assessment {
        // Authorization: Verify evaluator is assigned to this registration
        $registration = app(
            \Modules\Internship\Services\Contracts\InternshipRegistrationService::class,
        )->find($registrationId);

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
        $assessments = $this->query(['registration_id' => $registrationId])
            ->get()
            ->keyBy('type');

        return [
            'mentor' => $assessments->get('mentor'),
            'teacher' => $assessments->get('teacher'),
            'final_grade' => $this->calculateFinalGrade($assessments),
        ];
    }

    protected function calculateFinalGrade($assessments): ?float
    {
        $mentor = $assessments->get('mentor');
        $teacher = $assessments->get('teacher');

        if (! $mentor || ! $teacher) {
            return null;
        }

        // Simple 50-50 weighting for now, can be configured later
        return $mentor->score * 0.5 + $teacher->score * 0.5;
    }
}
