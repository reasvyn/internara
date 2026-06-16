<?php

declare(strict_types=1);

namespace App\Reports\Report\Actions;

use App\Assessment\Models\Assessment;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseCommandAction;
use App\Reports\Report\Events\GradeCalculated;
use App\Reports\Report\Models\Report;

final class CalculateFinalGradeAction extends BaseCommandAction
{
    private const array DEFAULT_WEIGHTS = [
        'supervisor' => 40,
        'teacher' => 20,
        'assignment' => 20,
        'exam' => 20,
    ];

    public function execute(Report $report): Report
    {
        return $this->transaction(function () use ($report) {
            $registration = $report->registration;

            $weights = $registration?->internship?->grading_weights ?? self::DEFAULT_WEIGHTS;

            $supervisorWeight = (int) ($weights['supervisor'] ?? 40);
            $teacherWeight = (int) ($weights['teacher'] ?? 20);
            $assignmentWeight = (int) ($weights['assignment'] ?? 20);
            $examWeight = (int) ($weights['exam'] ?? 20);
            $totalWeight = $supervisorWeight + $teacherWeight + $assignmentWeight + $examWeight;

            if ($totalWeight === 0) {
                $totalWeight = 100;
            }

            $supervisorScore = $report->supervisor_score ?? $this->getAverageAssessmentScore($registration?->id, 'supervisor');
            $teacherScore = $report->teacher_score ?? $this->getAverageAssessmentScore($registration?->id, 'teacher');
            $examScore = $report->exam_score ?? $this->getAverageExamScore($registration?->id);
            $assignmentAvg = $this->getAverageAssignmentScore($registration?->id);

            $finalScore = (($supervisorScore * $supervisorWeight)
                + ($teacherScore * $teacherWeight)
                + ($assignmentAvg * $assignmentWeight)
                + ($examScore * $examWeight))
                / $totalWeight;

            $finalScore = round(max(0, min(100, $finalScore)), 1);

            $gradeLetter = match (true) {
                $finalScore >= 90 => 'A',
                $finalScore >= 80 => 'B',
                $finalScore >= 70 => 'C',
                $finalScore >= 60 => 'D',
                default => 'E',
            };

            $report->update([
                'supervisor_score' => $supervisorScore ?: null,
                'teacher_score' => $teacherScore ?: null,
                'exam_score' => $examScore ?: null,
                'final_score' => $finalScore,
                'grade_letter' => $gradeLetter,
            ]);

            $this->log('final_grade_calculated', $report, [
                'final_score' => $finalScore,
                'grade_letter' => $gradeLetter,
                'weights' => $weights,
            ]);

            event(new GradeCalculated($report));

            return $report->fresh();
        });
    }

    private function getAverageAssessmentScore(?string $registrationId, string $evaluatorRole): float
    {
        if ($registrationId === null) {
            return 0;
        }

        return Assessment::where('registration_id', $registrationId)
            ->where('evaluator_role', $evaluatorRole)
            ->whereNotNull('score')
            ->average('score') ?? 0;
    }

    private function getAverageExamScore(?string $registrationId): float
    {
        if ($registrationId === null) {
            return 0;
        }

        return Assessment::where('registration_id', $registrationId)
            ->where('assessment_type', 'exam')
            ->whereNotNull('score')
            ->average('score') ?? 0;
    }

    private function getAverageAssignmentScore(?string $registrationId): float
    {
        if ($registrationId === null) {
            return 0;
        }

        return Submission::where('registration_id', $registrationId)
            ->whereNotNull('score')
            ->average('score') ?? 0;
    }
}
