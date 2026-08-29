<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;

final class RevokeCertificateAction extends BaseCommandAction
{
    public function execute(Certificate $certificate): Certificate
    {
        if ($certificate->status->isTerminal()) {
            throw new RejectedException(__('certificate.already_revoked'));
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
