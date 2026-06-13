<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Actions;

use App\Core\Actions\BaseAction;
use App\Enrollment\Registration\Models\Registration;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;

final class CreateSupervisionLogAction extends BaseAction
{
    public function execute(User $user, string $registrationId, array $data): SupervisionLog
    {
        return $this->transaction(function () use ($user, $registrationId, $data) {
            $registration = Registration::with('mentors')->findOrFail($registrationId);

            $isTeacher = $user->hasRole('teacher');

            $type = $isTeacher ? 'guidance' : 'mentoring';

            if ($isTeacher) {
                $log = SupervisionLog::create([
                    'registration_id' => $registrationId,
                    'supervisor_id' => $user->id,
                    'type' => $type,
                    'date' => $data['date'] ?? now()->toDateString(),
                    'topic' => $data['topic'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'status' => SupervisionLogStatus::COMPLETED->value,
                ]);
            } else {
                $log = SupervisionLog::create([
                    'registration_id' => $registrationId,
                    'supervisor_id' => $user->id,
                    'type' => $type,
                    'date' => $data['date'] ?? now()->toDateString(),
                    'topic' => $data['topic'] ?? null,
                    'notes' => $data['notes'] ?? null,
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
