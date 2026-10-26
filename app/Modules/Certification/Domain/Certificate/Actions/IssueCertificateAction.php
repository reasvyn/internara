<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Data\CertificateEligibility;
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
            $prefix = strtoupper(
                substr(
                    preg_replace('/[^A-Z0-9]/', '', $registration->internship?->name ?? 'PKL'),
                    0,
                    6,
                ),
            );

            $count = Certificate::whereYear('created_at', now()->year)
                ->lockForUpdate()
                ->count() + 1;
            $certificateNumber =
                "{$prefix}/".now()->year.'/'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $report = $registration->report;

            $qrHash = hash('sha256', implode('|', [
                $registration->mentee?->user?->id ?? '',
                setting('school.name', ''),
                (string) ($report?->score ?? ''),
                (string) auth()->id(),
                $certificateNumber,
            ]));

            $certificate = Certificate::create([
                'registration_id' => $registration->id,
                'certificate_number' => $certificateNumber,
                'template_content' => $template->content_template,
                'issued_by' => auth()->id(),
                'issued_at' => now(),
                'qr_hash' => $qrHash,
            ]);

            $this->log('certificate_issued', $certificate, [
                'certificate_number' => $certificateNumber,
                'registration_id' => $registration->id,
            ]);

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
