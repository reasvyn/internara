<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Internship;
use Illuminate\Support\Facades\DB;

class UpdateInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Internship $internship, array $data): Internship
    {
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
