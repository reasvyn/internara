<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Partners\Partnership\Models\Partnership;

final class TerminatePartnershipAction extends BaseAction
{
    public function execute(Partnership $partnership): Partnership
    {
        if (! $partnership->asPartnershipState()->isActive()) {
            throw new RejectedException('Only active partnerships can be terminated.');
        }

        return $this->transaction(function () use ($partnership) {
            $partnership->update(['status' => 'terminated']);

            $this->log('partnership_terminated', $partnership, [
                'agreement_number' => $partnership->agreement_number,
            ]);

            return $partnership->fresh();
        });
    }
}
