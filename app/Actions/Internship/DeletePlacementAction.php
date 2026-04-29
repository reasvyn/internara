<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipPlacement;
use Illuminate\Support\Facades\DB;

class DeletePlacementAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(InternshipPlacement $placement): void
    {
        DB::transaction(function () use ($placement) {
            $placementId = $placement->id;
            $placementName = $placement->name;

            $placement->delete();

            $this->logAudit->execute(
                action: 'placement_deleted',
                subjectType: InternshipPlacement::class,
                subjectId: $placementId,
                payload: ['name' => $placementName],
                module: 'Internship'
            );
        });
    }
}
