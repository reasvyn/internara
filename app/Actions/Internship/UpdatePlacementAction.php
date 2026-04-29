<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipPlacement;
use Illuminate\Support\Facades\DB;

class UpdatePlacementAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(InternshipPlacement $placement, array $data): InternshipPlacement
    {
        return DB::transaction(function () use ($placement, $data) {
            $placement->update($data);

            $this->logAudit->execute(
                action: 'placement_updated',
                subjectType: InternshipPlacement::class,
                subjectId: $placement->id,
                payload: ['name' => $placement->name, 'quota' => $placement->quota],
                module: 'Internship'
            );

            return $placement;
        });
    }
}
