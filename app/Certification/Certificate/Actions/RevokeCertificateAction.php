<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Actions;

use App\Certification\Certificate\Models\Certificate;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;

final class RevokeCertificateAction extends BaseAction
{
    public function execute(Certificate $certificate): Certificate
    {
        if ($certificate->status->isTerminal()) {
            throw new RejectedException('This certificate has already been revoked.');
        }

        return $this->transaction(function () use ($certificate) {
            $certificate->update([
                'status' => 'revoked',
                'revoked_by' => auth()->id(),
                'revoked_at' => now(),
            ]);

            $this->log('certificate_revoked', $certificate, [
                'certificate_number' => $certificate->certificate_number,
            ]);

            return $certificate->fresh();
        });
    }
}
