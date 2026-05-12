<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Internship $internship): void
    {
        if (! $internship->asInternshipState()->canBeDeleted()) {
            throw new RuntimeException('Cannot delete internship with active placements or registrations.');
        }

        DB::transaction(function () use ($internship) {
            $internshipId = $internship->id;
            $internshipName = $internship->name;

            $internship->delete();

            $this->logAudit->execute(
                action: 'internship_deleted',
                subjectType: Internship::class,
                subjectId: $internshipId,
                payload: ['name' => $internshipName],
                module: 'Internship',
            );
        });
    }
}
