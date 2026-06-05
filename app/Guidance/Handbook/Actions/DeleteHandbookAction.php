<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Actions;

use App\Core\Actions\BaseAction;
use App\Guidance\Handbook\Models\Handbook;

final class DeleteHandbookAction extends BaseAction
{
    public function execute(Handbook $handbook): void
    {
        $this->transaction(function () use ($handbook) {
            $this->log('handbook_deleted', $handbook, ['title' => $handbook->title]);
            $handbook->delete();
        });
    }
}
