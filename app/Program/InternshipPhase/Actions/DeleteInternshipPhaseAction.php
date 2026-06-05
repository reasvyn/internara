<?php

declare(strict_types=1);

namespace App\Program\InternshipPhase\Actions;

use App\Core\Actions\BaseAction;
use App\Program\Internship\Models\InternshipPhase;

final class DeleteInternshipPhaseAction extends BaseAction
{
    public function execute(InternshipPhase $phase): void
    {
        $this->transaction(function () use ($phase) {
            $this->log('internship_phase_deleted', $phase, ['name' => $phase->name]);

            $phase->delete();
        });
    }
}
