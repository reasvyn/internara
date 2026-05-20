<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Partnership\Models\Partnership;

class DeletePartnershipAction extends BaseAction
{
    public function execute(Partnership $partnership): void
    {
        $this->transaction(function () use ($partnership) {
            $this->log('partnership_deleted', $partnership, ['agreement_number' => $partnership->agreement_number]);

            $partnership->delete();
        });
    }
}
