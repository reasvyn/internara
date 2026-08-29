<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Document\Domain\Handbook\Events\HandbookDeleted;
use App\Modules\Document\Models\Document;

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
