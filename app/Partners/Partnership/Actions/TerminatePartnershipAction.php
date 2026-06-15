<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Partners\Partnership\Enums\PartnershipStatus;
use App\Partners\Partnership\Models\Partnership;

final class TerminatePartnershipAction extends BaseCommandAction
{
    public function execute(Partnership $partnership): Partnership
    {
        if (! $partnership->asPartnershipState()->isActive()) {
            throw new RejectedException('Only active partnerships can be terminated.');
        }

        return $this->transaction(function () use ($partnership) {
            $partnership->update(['status' => PartnershipStatus::TERMINATED->value]);

            $this->log('partnership_terminated', $partnership, [
                'agreement_number' => $partnership->agreement_number,
            ]);

            return $partnership->fresh();
        });
    }
}
