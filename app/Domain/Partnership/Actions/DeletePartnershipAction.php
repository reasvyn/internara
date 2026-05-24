<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Partnership\Models\Partnership;

class DeletePartnershipAction extends BaseAction
{
    public function execute(Partnership $partnership): void
    {
        if (! $partnership->asPartnershipState()->canBeDeleted()) {
            throw new RejectedException('Only expired or terminated partnerships can be deleted.');
        }

        $this->transaction(function () use ($partnership) {
            $this->log('partnership_deleted', $partnership, ['agreement_number' => $partnership->agreement_number]);

            $partnership->delete();
        });
    }
}
