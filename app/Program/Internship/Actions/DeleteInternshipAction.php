<?php

declare(strict_types=1);

namespace App\Program\Internship\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Program\Internship\Models\Internship;

final class DeleteInternshipAction extends BaseAction
{
    public function execute(Internship $internship): void
    {
        if (! $internship->asInternshipState()->canBeDeleted()) {
            throw new RejectedException(
                'Cannot delete internship with active placements or registrations.',
            );
        }

        $this->transaction(function () use ($internship) {
            $this->log('internship_deleted', $internship, ['name' => $internship->name]);

            $internship->delete();
        });
    }
}
