<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\AbsenceRequest\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Journals\Aggregates\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Domain\Journals\Aggregates\AbsenceRequest\Models\AbsenceRequest;
use App\Domain\User\Models\User;

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
