<?php

declare(strict_types=1);

namespace App\Program\InternshipPhase\Actions;

use App\Core\Actions\BaseAction;
use App\Program\Internship\Models\InternshipPhase;

final class CreateInternshipPhaseAction extends BaseAction
{
    public function execute(array $data): InternshipPhase
    {
        return $this->transaction(function () use ($data) {
            $phase = InternshipPhase::create($data);

            $this->log('internship_phase_created', $phase, $data);

            return $phase;
        });
    }
}
