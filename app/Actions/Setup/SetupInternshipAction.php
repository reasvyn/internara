<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Audit\LogAuditAction;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;

/**
 * Setup the initial Internship Program.
 */
class SetupInternshipAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(array $data): Internship
    {
        return DB::transaction(function () use ($data) {
            $internship = Internship::create($data);

            $this->logAudit->execute(
                action: 'internship_setup_completed',
                subjectType: Internship::class,
                subjectId: $internship->id,
                payload: $data,
                module: 'Setup'
            );

            return $internship;
        });
    }
}
