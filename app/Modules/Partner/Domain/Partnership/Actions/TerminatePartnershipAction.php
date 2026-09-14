<?php

declare(strict_types=1);

namespace App\Modules\Partner\Domain\Partnership\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partner\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipTerminated;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;

final class TerminatePartnershipAction extends BaseCommandAction
{
    public function execute(Partnership $partnership): Partnership
    {
        if (! $partnership->asPartnershipState()->isActive()) {
            throw new RejectedException(__('partnership.terminate_blocked'));
        }

        return $this->transaction(function () use ($partnership) {
            $partnership->update(['status' => PartnershipStatus::TERMINATED->value]);

            $this->log('partnership_terminated', $partnership, [
                'agreement_number' => $partnership->agreement_number,
            ]);

            event(new PartnershipTerminated($partnership));

            return $partnership->fresh();
        });
    }
}
