<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Journals\SupervisionLog\Data\CreateLogData;
use App\Journals\SupervisionLog\Enums\SupervisionLogStatus;
use App\Journals\SupervisionLog\Models\SupervisionLog;

final class CreateLogAction extends BaseCommandAction
{
    public function execute(CreateLogData $data): SupervisionLog
    {
        return $this->transaction(function () use ($data) {
            $log = SupervisionLog::create([
                'registration_id' => $data->registrationId,
                'supervisor_id' => $data->data['supervisor_id'],
                'date' => $data->data['date'] ?? now()->toDateString(),
                'topic' => $data->data['topic'] ?? null,
                'notes' => $data->data['notes'] ?? null,
                'status' => SupervisionLogStatus::DRAFT->value,
            ]);

            $this->log('supervision_log_created', $log, [
                'topic' => $log->topic,
            ]);

            return $log;
        });
    }
}
