<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\SupervisionLog\Enums\SupervisionLogStatus;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;

final class CreateLogAction extends BaseCommandAction
{
    public function execute(User $student, string $registrationId, array $data): SupervisionLog
    {
        return $this->transaction(function () use ($registrationId, $data) {
            $log = SupervisionLog::create([
                'registration_id' => $registrationId,
                'supervisor_id' => $data['supervisor_id'],
                'date' => $data['date'] ?? now()->toDateString(),
                'topic' => $data['topic'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => SupervisionLogStatus::DRAFT->value,
            ]);

            $this->log('supervision_log_created', $log, [
                'topic' => $log->topic,
            ]);

            return $log;
        });
    }
}
