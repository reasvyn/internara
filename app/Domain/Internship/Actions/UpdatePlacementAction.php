<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Placement;
use Illuminate\Support\Facades\DB;

class UpdatePlacementAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Placement $placement, array $data): Placement
    {
        return DB::transaction(function () use ($placement, $data) {
            $placement->update($data);

            $this->logAudit->execute(
                action: 'placement_updated',
                subjectType: Placement::class,
                subjectId: $placement->id,
                payload: ['name' => $placement->name, 'quota' => $placement->quota],
                module: 'Internship',
            );

            return $placement;
        });
    }
}
