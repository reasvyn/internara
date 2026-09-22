<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Data\CertificateStatusView;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Actions\BaseReadAction;

final class ReadCertificateStatusAction extends BaseReadAction
{
    public function execute(string $qrHash): CertificateStatusView
    {
        if (! preg_match('/^[A-Za-z0-9]{32,64}$/', $qrHash)) {
            return CertificateStatusView::notFound();
        }

        $certificate = Certificate::where('qr_hash', $qrHash)
            ->with(['registration.student'])
            ->first();

        if ($certificate === null) {
            return CertificateStatusView::notFound();
        }

        $studentName = $certificate->registration?->student?->name;
        $schoolName = (string) setting('school.name', (string) config('app.name', 'Internara'));

        return new CertificateStatusView(
            status: $certificate->status->value,
            studentName: $studentName,
            schoolName: $schoolName !== '' ? $schoolName : 'Internara',
            certificateNumber: $certificate->certificate_number,
            issuedAt: $certificate->issued_at?->toDateString(),
            revokedAt: $certificate->revoked_at?->toDateString(),
        );
    }
}
