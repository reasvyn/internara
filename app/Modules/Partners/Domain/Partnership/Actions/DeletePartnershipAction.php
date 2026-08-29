<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipDeleted;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;

final class DeletePartnershipAction extends BaseCommandAction
{
    public function execute(Partnership $partnership): void
    {
        if (! $partnership->asPartnershipState()->canBeDeleted()) {
            throw new RejectedException(__('partnership.delete_blocked'));
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
