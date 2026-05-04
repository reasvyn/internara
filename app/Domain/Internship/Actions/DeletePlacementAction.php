<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Placement;
use Illuminate\Support\Facades\DB;

class DeletePlacementAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Placement $placement): void
    {
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
