<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\SupervisionLog\Data\CreateSupervisionLogData;
use App\Journals\SupervisionLog\Enums\SupervisionLogStatus;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;

final class CreateSupervisionLogAction extends BaseCommandAction
{
    public function execute(CreateSupervisionLogData $data): SupervisionLog
    {
        return $this->transaction(function () use ($data) {
            $user = User::findOrFail($data->userId);
            $registration = Registration::with('mentors')->findOrFail($data->registrationId);

            $isTeacher = $user->hasRole('teacher');

            $type = $isTeacher ? 'guidance' : 'mentoring';

            if ($isTeacher) {
                $log = SupervisionLog::create([
                    'registration_id' => $data->registrationId,
                    'supervisor_id' => $user->id,
                    'type' => $type,
                    'date' => $data->data['date'] ?? now()->toDateString(),
                    'topic' => $data->data['topic'] ?? null,
                    'notes' => $data->data['notes'] ?? null,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'status' => SupervisionLogStatus::COMPLETED->value,
                ]);
            } else {
                $log = SupervisionLog::create([
                    'registration_id' => $data->registrationId,
                    'supervisor_id' => $user->id,
                    'type' => $type,
                    'date' => $data->data['date'] ?? now()->toDateString(),
                    'topic' => $data->data['topic'] ?? null,
                    'notes' => $data->data['notes'] ?? null,
                    'is_verified' => false,
                    'status' => SupervisionLogStatus::SUBMITTED->value,
                ]);
            }

            $this->log('supervision_log_created', $log, [
                'type' => $log->type,
                'topic' => $log->topic,
            ]);

            return $log;
        });
    }
}
