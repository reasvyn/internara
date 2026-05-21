<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Mentor\Enums\SupervisionLogStatus;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;

class CreateSupervisionLogAction extends BaseAction
{
    public function execute(User $user, string $registrationId, array $data): SupervisionLog
    {
        return $this->transaction(function () use ($user, $registrationId, $data) {
            $registration = Registration::with('mentors')->findOrFail($registrationId);

            $isTeacher = Mentor::where('user_id', $user->id)
                ->where('type', Mentor::TYPE_SCHOOL_TEACHER)
                ->whereHas('registrations', fn ($q) => $q->where('id', $registrationId))
                ->exists();

            $type = $isTeacher ? 'guidance' : 'mentoring';

            if ($isTeacher) {
                $log = SupervisionLog::create([
                    'registration_id' => $registrationId,
                    'supervisor_id' => $user->id,
                    'type' => $type,
                    'date' => $data['date'] ?? now()->toDateString(),
                    'topic' => $data['topic'] ?? null,
                    'notes' => $data['notes'] ?? null,
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

            $this->log('supervision_log_created', $log, ['type' => $log->type, 'topic' => $log->topic]);

            return $log;
        });
    }
}
