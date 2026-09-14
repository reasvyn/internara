<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\MonitoringVisit\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journal\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\User\Models\User;

final class VerifyVisitAction extends BaseCommandAction
{
    public function execute(MonitoringVisit $visit, User $admin): MonitoringVisit
    {
        if ($visit->is_verified) {
            throw new RejectedException(__('journal.visit_already_verified'));
        }

        return $this->transaction(function () use ($visit, $admin) {
            $visit->update([
                'is_verified' => true,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $this->log('monitoring_visit_verified', $visit, [
                'verified_by' => $admin->id,
            ]);

            return $visit->fresh();
        });
    }
}
