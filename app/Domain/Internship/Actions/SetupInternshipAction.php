<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Internship;
use Illuminate\Support\Facades\DB;

/**
 * Setup the initial Internship Program.
 */
class SetupInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @param array{name: string, description: ?string, start_date: string, end_date: string, status: string} $data
     */
    public function execute(array $data): Internship
    {
        return DB::transaction(function () use ($data) {
            $internship = Internship::create($data);

            $this->logAudit->execute(
                action: 'internship_setup_completed',
                subjectType: Internship::class,
                subjectId: $internship->id,
                payload: $data,
                module: 'Setup',
            );

            return $internship;
        });
    }
}
