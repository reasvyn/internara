<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Enums\Internship\InternshipStatus;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;

class UpdateInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Internship $internship, array $data): Internship
    {
        if (isset($data['status'])) {
            $newStatus = InternshipStatus::tryFrom($data['status']);
            if ($newStatus !== null && $newStatus !== $internship->status) {
                if (! $internship->status->canTransitionTo($newStatus)) {
                    throw new \RuntimeException(
                        __('internship.invalid_status_transition', [
                            'from' => __("internship.statuses.{$internship->status->value}"),
                            'to' => __("internship.statuses.{$newStatus->value}"),
                        ]),
                    );
                }
            }
        }

        return DB::transaction(function () use ($internship, $data) {
            $internship->update($data);

            $this->logAudit->execute(
                action: 'internship_updated',
                subjectType: Internship::class,
                subjectId: $internship->id,
                payload: ['name' => $internship->name],
                module: 'Internship',
            );

            return $internship;
        });
    }
}
