<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Program\Aggregates\Internship\Models\Internship;

final class DeleteInternshipAction extends BaseAction
{
    public function execute(Internship $internship): void
    {
        if (! $internship->asInternshipState()->canBeDeleted()) {
            throw new RejectedException('Cannot delete internship with active placements or registrations.');
        }

        $this->transaction(function () use ($internship) {
            $this->log('internship_deleted', $internship, ['name' => $internship->name]);

            $internship->delete();
        });
    }
}
