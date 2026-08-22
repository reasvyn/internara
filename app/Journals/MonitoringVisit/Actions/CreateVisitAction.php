<?php

declare(strict_types=1);

namespace App\Journals\MonitoringVisit\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\MonitoringVisit\Data\CreateVisitData;
use App\Journals\MonitoringVisit\Models\MonitoringVisit;

final class CreateVisitAction extends BaseCommandAction
{
    public function execute(CreateVisitData $data): MonitoringVisit
    {
        return $this->transaction(function () use ($data) {
            $visit = MonitoringVisit::create([
                'registration_id' => $data->registrationId,
                'teacher_id' => $data->teacherId,
                'visit_date' => $data->data['visit_date'] ?? now()->toDateString(),
                'method' => $data->data['method'],
                'location' => $data->data['location'] ?? null,
                'duration_minutes' => $data->data['duration_minutes'] ?? null,
                'notes' => $data->data['notes'] ?? null,
                'student_condition' => $data->data['student_condition'] ?? null,
                'company_feedback' => $data->data['company_feedback'] ?? null,
                'follow_up_actions' => $data->data['follow_up_actions'] ?? null,
                'is_verified' => false,
            ]);

            $this->log('monitoring_visit_created', $visit, [
                'teacher_id' => $data->teacherId,
                'registration_id' => $data->registrationId,
            ]);

            return $visit;
        });
    }
}
