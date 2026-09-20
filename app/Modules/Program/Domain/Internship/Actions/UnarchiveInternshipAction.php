<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\Internship\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\Program\Domain\Internship\Models\Internship;

final class UnarchiveInternshipAction extends BaseCommandAction
{
    public function execute(Internship $internship, string $reason): Internship
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            throw new RejectedException(__('internship.unarchive_super_admin_only'));
        }

        if ($reason === '') {
            throw new RejectedException(__('internship.unarchive_reason_required'));
        }

        return $this->transaction(function () use ($internship, $reason): Internship {
            if ($internship->status !== InternshipStatus::ARCHIVED) {
                throw new RejectedException(__('internship.unarchive_archived_only'));
            }

            $internship->update(['status' => InternshipStatus::COMPLETED->value]);
            $this->log('internship_unarchived', $internship, ['reason' => $reason]);

            return $internship->refresh();
        });
    }
}
