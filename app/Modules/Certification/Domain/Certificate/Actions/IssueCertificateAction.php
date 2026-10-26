<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Data\CertificateEligibility;
use App\Modules\Certification\Domain\Certificate\Entities\CertificateEntity;
use App\Modules\Certification\Domain\Certificate\Events\CertificateIssued;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class IssueCertificateAction extends BaseCommandAction
{
    public function __construct(
        private readonly ReadCertificateEligibilityAction $eligibility,
    ) {}

    public function execute(Registration $registration, CertificateTemplate $template): Certificate
    {
        $eligibility = $this->eligibility->execute($registration);

        if (! $eligibility->isEligible()) {
            throw new RejectedException(__('certificate.ineligible', [
                'conditions' => $this->describesUnmetGates($eligibility),
            ]));
        }

        return $this->transaction(function () use ($registration, $template) {
            $year = (int) now()->year;

            // lockForUpdate on the year's serials: concurrent workers take turns, and
            // the entity below rejects a serial or hash already on record (FR-CERT-006).
            $issued = Certificate::whereYear('created_at', $year)
                ->lockForUpdate()
                ->get(['certificate_number', 'qr_hash']);

            $number = CertificateEntity::composeSerial(
                (string) ($registration->internship?->name ?? ''),
                $year,
                $issued->count() + 1,
            );

            $draft = CertificateEntity::forIssuance(
                certificateNumber: $number,
                qrHash: CertificateEntity::composeQrHash(
                    studentId: (string) ($registration->mentee?->user?->id ?? ''),
                    schoolName: (string) setting('school.name', ''),
                    score: (string) ($registration->report?->score ?? ''),
                    issuerId: (string) auth()->id(),
                    certificateNumber: $number,
                ),
            );

            // The digest embeds the serial, and the serial embeds the year, so a
            // cross-year duplicate cannot exist; checking this year's rows is enough.
            $draft->assertUniqueAgainst(
                $issued->pluck('certificate_number')->all(),
                $issued->pluck('qr_hash')->all(),
            );

            $certificate = Certificate::create([
                'registration_id' => $registration->id,
                'certificate_number' => $draft->certificateNumber(),
                'template_content' => $template->content_template,
                'issued_by' => auth()->id(),
                'issued_at' => now(),
                'qr_hash' => $draft->qrHash(),
            ]);

            $this->log(
                'certificate_issued',
                $certificate,
                CertificateEntity::fromModel($certificate)->auditMetadata(),
            );

            event(new CertificateIssued($certificate));

            return $certificate->fresh();
        });
    }

    /**
     * Name every unmet gate in the admin's language, so the issuance screen doubles
     * as a graduation-readiness checklist (FR-CERT-011).
     */
    private function describesUnmetGates(CertificateEligibility $eligibility): string
    {
        return implode(', ', array_map(
            static fn (string $gate): string => (string) __('certificate.gate.'.$gate),
            $eligibility->unmetGates(),
        ));
    }
}
