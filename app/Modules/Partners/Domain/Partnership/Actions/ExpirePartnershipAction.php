<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipExpired;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Support\Carbon;

final class ExpirePartnershipAction extends BaseCommandAction
{
    public function execute(Partnership $partnership): Partnership
    {
        if ($partnership->is_active === false) {
            throw new RejectedException('Cannot expire an inactive partnership.');
        }

        if ($partnership->terminated_at !== null) {
            throw new RejectedException('Cannot expire an already terminated partnership.');
        }

        return $this->transaction(function () use ($partnership) {
            $partnership->update([
                'is_active' => false,
                'terminated_at' => Carbon::now(),
            ]);

            $this->dispatchEvent(new PartnershipExpired($partnership));

            $this->log('partnership_expired', $partnership);

            return $partnership;
        });
    }
}
