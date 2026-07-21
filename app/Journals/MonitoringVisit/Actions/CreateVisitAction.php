<?php

declare(strict_types=1);

namespace App\Journals\MonitoringVisit\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\MonitoringVisit\Models\MonitoringVisit;
use App\User\Models\User;

final class CreateVisitAction extends BaseCommandAction
{
    public function execute(User $teacher, string $registrationId, array $data): MonitoringVisit
    {
        return $this->transaction(function () use ($teacher, $registrationId, $data) {
            $visit = MonitoringVisit::create([
                'registration_id' => $registrationId,
                'teacher_id' => $teacher->id,
                'visit_date' => $data['visit_date'] ?? now()->toDateString(),
                'method' => $data['method'],
                'location' => $data['location'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'notes' => $data['notes'] ?? null,
                'student_condition' => $data['student_condition'] ?? null,
                'company_feedback' => $data['company_feedback'] ?? null,
                'follow_up_actions' => $data['follow_up_actions'] ?? null,
                'is_verified' => false,
            ]);

            $this->log('monitoring_visit_created', $visit, [
                'teacher_id' => $teacher->id,
                'registration_id' => $registrationId,
            ]);

            return $visit;
        });
    }
}
