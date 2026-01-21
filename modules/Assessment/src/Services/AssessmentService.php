<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Services\Contracts\AssessmentService as Contract;
use Modules\Exception\AppException;
use Modules\Shared\Services\EloquentQuery;

class AssessmentService extends EloquentQuery implements Contract
{
    public function __construct(
        protected \Modules\Assessment\Services\Contracts\ComplianceService $complianceService,
    ) {
        $this->setModel(new Assessment);
        $this->setSearchable(['type', 'academic_year']);
        $this->setSortable(['created_at', 'score']);
    }
...
    public function getScoreCard(string $registrationId): array
    {
        $assessments = $this->query(['registration_id' => $registrationId])
            ->get()
            ->keyBy('type');

        $compliance = $this->complianceService->calculateScore($registrationId);

        return [
            'mentor' => $assessments->get('mentor'),
            'teacher' => $assessments->get('teacher'),
            'compliance' => $compliance,
            'final_grade' => $this->calculateFinalGrade($assessments, $compliance['final_score'] ?? 0),
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
        return round(
            ($mentor->score * 0.4) +
            ($teacher->score * 0.4) +
            ($complianceScore * 0.2),
            2
        );
    }
}
