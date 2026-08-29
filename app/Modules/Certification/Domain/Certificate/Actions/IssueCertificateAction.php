<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Events\CertificateIssued;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class IssueCertificateAction extends BaseCommandAction
{
    public function execute(Registration $registration, CertificateTemplate $template): Certificate
    {
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
}
