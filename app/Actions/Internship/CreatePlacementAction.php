<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipPlacement;
use Illuminate\Support\Facades\DB;

class CreatePlacementAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): InternshipPlacement
    {
        return DB::transaction(function () use ($data) {
            $data['filled_quota'] = 0;
            $placement = InternshipPlacement::create($data);

            $this->logAudit->execute(
                action: 'placement_created',
                subjectType: InternshipPlacement::class,
                subjectId: $placement->id,
                payload: ['name' => $placement->name, 'quota' => $placement->quota],
                module: 'Internship'
            );

            return $placement;
        });
    }
}
