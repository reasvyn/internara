<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Placement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeletePlacementAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Placement $placement): void
    {
        if (! $placement->asPlacementState()->canBeDeleted()) {
            throw new RuntimeException('Cannot delete placement with active registrations.');
        }

        DB::transaction(function () use ($placement) {
            $placementId = $placement->id;
            $placementName = $placement->name;

            $placement->delete();

            $this->logAudit->execute(
                action: 'placement_deleted',
                subjectType: Placement::class,
                subjectId: $placementId,
                payload: ['name' => $placementName],
                module: 'Internship',
            );
        });
    }
}
