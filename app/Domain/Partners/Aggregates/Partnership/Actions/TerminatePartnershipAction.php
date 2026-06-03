<?php

declare(strict_types=1);

namespace App\Domain\Partners\Aggregates\Partnership\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Partners\Aggregates\Partnership\Models\Partnership;

final class TerminatePartnershipAction extends BaseAction
{
    public function execute(Partnership $partnership): Partnership
    {
        if (! $partnership->asPartnershipState()->isActive()) {
            throw new RejectedException('Only active partnerships can be terminated.');
        }

        return $this->transaction(function () use ($partnership) {
            $partnership->update(['status' => 'terminated']);

            $this->log('partnership_terminated', $partnership, ['agreement_number' => $partnership->agreement_number]);

            return $partnership->fresh();
        });
    }
}
