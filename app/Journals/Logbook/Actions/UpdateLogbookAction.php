<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Actions;

use App\Core\Actions\BaseAction;
use App\Journals\Logbook\Models\Logbook;

final class UpdateLogbookAction extends BaseAction
{
    public function execute(Logbook $entry, array $data): Logbook
    {
        return $this->transaction(function () use ($entry, $data) {
            $updateData = array_filter(
                [
                    'content' => $data['content'] ?? null,
                    'learning_outcomes' => $data['learning_outcomes'] ?? null,
                    'status' => $data['status'] ?? null,
                    'is_verified' => $data['is_verified'] ?? null,
                    'verified_by' => isset($data['is_verified']) && $data['is_verified'] ? auth()->id() : null,
                    'verified_at' => isset($data['is_verified']) && $data['is_verified'] ? now() : null,
                    'mentor_feedback' => $data['mentor_feedback'] ?? null,
                    'supervisor_note' => $data['supervisor_note'] ?? null,
                    'supervisor_id' => $data['supervisor_id'] ?? null,
                    'supervisor_reviewed_at' => $data['supervisor_reviewed_at'] ?? null,
                ],
                fn ($v) => $v !== null,
            );

            if ($updateData !== []) {
                $entry->update($updateData);
            }

            $this->log('logbook_entry_updated', $entry, [
                'user_id' => $entry->user_id,
                'date' => $entry->date?->toDateString(),
                'status' => $entry->status?->value,
            ]);

            return $entry;
        });
    }
}
