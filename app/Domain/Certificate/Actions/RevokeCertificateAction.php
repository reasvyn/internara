<?php

declare(strict_types=1);

namespace App\Domain\Certificate\Actions;

use App\Domain\Certificate\Models\Certificate;
use App\Domain\Core\Actions\BaseAction;
use RuntimeException;

class RevokeCertificateAction extends BaseAction
{
    public function execute(Certificate $certificate): Certificate
    {
        if ($certificate->status->isTerminal()) {
            throw new RuntimeException('This certificate has already been revoked.');
        }

        return $this->transaction(function () use ($certificate) {
            $certificate->update([
                'status' => 'revoked',
                'revoked_by' => auth()->id(),
                'revoked_at' => now(),
            ]);

            $this->log('certificate_revoked', $certificate, ['certificate_number' => $certificate->certificate_number]);

            return $certificate->fresh();
        });
    }
}
