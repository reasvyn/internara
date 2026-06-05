<?php

declare(strict_types=1);

namespace App\Program\InternshipPhase\Actions;

use App\Core\Actions\BaseAction;
use App\Program\Internship\Models\InternshipPhase;

final class UpdateInternshipPhaseAction extends BaseAction
{
    public function execute(InternshipPhase $phase, array $data): InternshipPhase
    {
        return $this->transaction(function () use ($phase, $data) {
            $phase->update($data);

            $this->log('internship_phase_updated', $phase, $data);

            return $phase;
        });
    }
}
