<?php

declare(strict_types=1);

namespace App\Actions\Evaluation;

use App\Actions\Core\LogAuditAction;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EvaluateMentorAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $evaluator, User $mentor, array $data, ?Evaluation $existing = null): Evaluation
    {
        return DB::transaction(function () use ($evaluator, $mentor, $data, $existing) {
            if ($existing) {
                $existing->update([
                    'overall_score' => $data['overall_score'] ?? null,
                    'feedback' => $data['feedback'] ?? null,
                    'criteria_scores' => $data['criteria_scores'] ?? [],
                ]);

                $this->logAudit->execute(
                    action: 'evaluation_updated',
                    subjectType: Evaluation::class,
                    subjectId: $existing->id,
                    module: 'Evaluation',
                );

                return $existing;
            }

            $evaluation = Evaluation::create([
                'evaluator_id' => $evaluator->id,
                'mentor_id' => $mentor->id,
                'overall_score' => $data['overall_score'] ?? null,
                'feedback' => $data['feedback'] ?? null,
                'criteria_scores' => $data['criteria_scores'] ?? [],
            ]);

            $this->logAudit->execute(
                action: 'evaluation_created',
                subjectType: Evaluation::class,
                subjectId: $evaluation->id,
                module: 'Evaluation',
            );

            return $evaluation;
        });
    }
}
