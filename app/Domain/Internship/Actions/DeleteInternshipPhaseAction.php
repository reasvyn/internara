<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\InternshipPhase;

class DeleteInternshipPhaseAction extends BaseAction
{
    public function execute(InternshipPhase $phase): void
    {
        $this->transaction(function () use ($phase) {
            $this->log('internship_phase_deleted', $phase, ['name' => $phase->name]);

            $phase->delete();
        });
    }
}
