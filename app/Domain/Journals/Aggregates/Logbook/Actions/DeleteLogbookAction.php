<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\Logbook\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Journals\Aggregates\Logbook\Models\Logbook;

final class DeleteLogbookAction extends BaseAction
{
    public function execute(Logbook $entry): void
    {
        $this->transaction(function () use ($entry) {
            $this->log('logbook_entry_deleted', $entry, [
                'user_id' => $entry->user_id,
                'date' => $entry->date?->toDateString(),
            ]);

            $entry->delete();
        });
    }
}
