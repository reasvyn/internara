<?php

declare(strict_types=1);

namespace App\Modules\Partner\Domain\Partnership\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partner\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipExpired;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;

final class ExpirePartnershipAction extends BaseCommandAction
{
    public function execute(Partnership $partnership): Partnership
    {
        if (! $partnership->asPartnershipState()->isActive()) {
            throw new RejectedException(__('partnership.expire_blocked'));
        }

        return $this->transaction(function () use ($partnership) {
            $partnership->update([
                'status' => PartnershipStatus::EXPIRED->value,
            ]);

            $this->dispatchEvent(new PartnershipExpired($partnership));

            $this->log('partnership_expired', $partnership, [
                'agreement_number' => $partnership->agreement_number,
            ]);

            return $partnership->fresh();
        });
    }
}
