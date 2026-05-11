<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Actions\Core\LogAuditAction;
use App\Enums\Mentor\SupervisionLogStatus;
use App\Models\Mentor;
use App\Models\Registration;
use App\Models\SupervisionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateSupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, string $registrationId, array $data): SupervisionLog
    {
        return DB::transaction(function () use ($user, $registrationId, $data) {
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
                    'is_verified' => false,
                    'verified_at' => null,
                    'status' => SupervisionLogStatus::SUBMITTED->value,
                ]);
            }

            $this->logAudit->execute(
                action: 'supervision_log_created',
                subjectType: SupervisionLog::class,
                subjectId: $log->id,
                payload: ['type' => $log->type, 'topic' => $log->topic],
                module: 'Supervision',
            );

            return $log;
        });
    }
}
