<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Guidance\Models\Handbook;

class DeleteHandbookAction extends BaseAction
{
    public function execute(Handbook $handbook): void
    {
        $this->transaction(function () use ($handbook) {
            $this->log('handbook_deleted', $handbook, ['title' => $handbook->title]);
            $handbook->delete();
        });
    }
}
