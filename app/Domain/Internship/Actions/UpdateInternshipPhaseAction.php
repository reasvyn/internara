<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\InternshipPhase;

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
