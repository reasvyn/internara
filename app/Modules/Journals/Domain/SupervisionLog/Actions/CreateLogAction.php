<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Journals\Domain\SupervisionLog\Data\CreateLogData;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;

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
