<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentor\SupervisionLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateSupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $teacher, string $registrationId, array $data): SupervisionLog
    {
        return DB::transaction(function () use ($teacher, $registrationId, $data) {
            $registration = Registration::findOrFail($registrationId);

            $type = $registration->teacher_id === $teacher->id ? 'guidance' : 'mentoring';

            $log = SupervisionLog::create([
                'registration_id' => $registrationId,
                'supervisor_id' => $teacher->id,
                'type' => $type,
                'date' => $data['date'] ?? now()->toDateString(),
                'topic' => $data['topic'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_verified' => $data['is_verified'] ?? false,
                'verified_at' => isset($data['is_verified']) && $data['is_verified'] ? now() : null,
                'status' => $data['status'] ?? 'in_progress',
            ]);

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
