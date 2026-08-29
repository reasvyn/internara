<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\Internship\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\Program\Domain\Internship\Models\Internship;

final class UpdateInternshipAction extends BaseCommandAction
{
    public function execute(Internship $internship, array $data): Internship
    {
        if (isset($data['status'])) {
            $newStatus = InternshipStatus::tryFrom($data['status']);
            if ($newStatus !== null && $newStatus !== $internship->status) {
                if (! $internship->status->canTransitionTo($newStatus)) {
                    throw new RejectedException(
                        __('internship.invalid_status_transition', [
                            'from' => __("internship.statuses.{$internship->status->value}"),
                            'to' => __("internship.statuses.{$newStatus->value}"),
                        ]),
                    );
                }
            }
        }

        return $this->transaction(function () use ($internship, $data) {
            $internship->update($data);

            $this->log('internship_updated', $internship, ['name' => $internship->name]);

            return $internship;
        });
    }
}
