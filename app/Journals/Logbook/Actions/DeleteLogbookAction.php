<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Actions;

use App\Core\Actions\BaseAction;
use App\Journals\Logbook\Models\Logbook;

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
