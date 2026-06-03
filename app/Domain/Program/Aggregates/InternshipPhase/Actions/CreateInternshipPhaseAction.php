<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\InternshipPhase\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Program\Aggregates\Internship\Models\InternshipPhase;

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
