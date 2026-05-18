<?php

declare(strict_types=1);

namespace App\Actions\Certificate;

use App\Actions\Core\LogAuditAction;
use App\Models\Certificate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RevokeCertificateAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Certificate $certificate): Certificate
    {
        if ($certificate->status->isTerminal()) {
            throw new RuntimeException('This certificate has already been revoked.');
        }

        return DB::transaction(function () use ($certificate) {
            $certificate->update([
                'status' => 'revoked',
                'revoked_by' => auth()->id(),
                'revoked_at' => now(),
            ]);

            $this->logAudit->execute(
                action: 'certificate_revoked',
                subjectType: Certificate::class,
                subjectId: $certificate->id,
                payload: ['certificate_number' => $certificate->certificate_number],
                module: 'Certificate',
            );

            return $certificate->fresh();
        });
    }
}
