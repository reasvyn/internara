<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\User\Models\User;

final class ProcessAbsenceAction extends BaseAction
{
    public function execute(AbsenceRequest $absence, User $processor, AbsenceRequestStatus $status, ?string $notes = null): AbsenceRequest
    {
        if ($absence->status->isProcessed()) {
            throw new RejectedException('This absence request has already been processed.');
        }

        return $this->transaction(function () use ($absence, $processor, $status, $notes) {
            $absence->update([
                'status' => $status,
                'processed_by' => $processor->id,
                'processed_at' => now(),
                'admin_notes' => $notes,
            ]);

            $this->log('absence_request_'.$status->value, $absence, [
                'status' => $status->value,
            ]);

            return $absence;
        });
    }
}
