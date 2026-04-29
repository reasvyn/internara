<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;

class CreateInternshipAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(array $data): Internship
    {
        return DB::transaction(function () use ($data) {
            $internship = Internship::create($data);

            $this->logAudit->execute(
                action: 'internship_created',
                subjectType: Internship::class,
                subjectId: $internship->id,
                payload: ['name' => $internship->name],
                module: 'Internship'
            );

            return $internship;
        });
    }
}
