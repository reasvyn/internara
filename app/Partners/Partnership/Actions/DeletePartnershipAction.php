<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Partners\Partnership\Events\PartnershipDeleted;
use App\Partners\Partnership\Models\Partnership;

final class DeletePartnershipAction extends BaseCommandAction
{
    public function execute(Partnership $partnership): void
    {
        if (! $partnership->asPartnershipState()->canBeDeleted()) {
            throw new RejectedException('Only expired or terminated partnerships can be deleted.');
        }

        $this->transaction(function () use ($partnership) {
            $this->log('partnership_deleted', $partnership, [
                'agreement_number' => $partnership->agreement_number,
            ]);

            event(new PartnershipDeleted($partnership));

            $partnership->delete();
        });
    }
}
