<?php

declare(strict_types=1);

namespace App\Document\Handbook\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Document\Handbook\Events\HandbookDeleted;
use App\Document\Models\Document;

final class DeleteHandbookAction extends BaseCommandAction
{
    public function execute(Document $handbook): void
    {
        $this->transaction(function () use ($handbook) {
            $this->log('handbook_deleted', $handbook, [
                'title' => $handbook->title,
            ]);

            event(new HandbookDeleted($handbook));

            $handbook->delete();
        });
    }
}
