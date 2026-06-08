<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Actions;

use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Certification\Certificate\Support\CertificateRenderer;
use App\Core\Actions\BaseAction;
use App\Enrollment\Registration\Models\Registration;

final class IssueCertificateAction extends BaseAction
{
    public function __construct(protected readonly CertificateRenderer $renderer) {}

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

            $count = Certificate::whereYear('created_at', now()->year)->count() + 1;
            $certificateNumber =
                "{$prefix}/".now()->year.'/'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $report = $registration->report;

            $metadata = [
                'student_name' => $registration->mentee?->user?->name ?? '',
                'school_name' => $registration->internship?->academicYear?->name ?? '',
                'company_name' => $registration->placement?->company?->name ?? '',
                'start_date' => $registration->start_date?->format('Y-m-d'),
                'end_date' => $registration->end_date?->format('Y-m-d'),
                'score' => $report?->score,
            ];

            $certificate = Certificate::create([
                'registration_id' => $registration->id,
                'certificate_number' => $certificateNumber,
                'template_id' => $template->id,
                'issued_by' => auth()->id(),
                'issued_at' => now(),
                'metadata' => $metadata,
            ]);

            $pdfPath = $this->renderer->storePdf($registration, $certificate);

            $certificate->update(['metadata' => array_merge($metadata, ['pdf_path' => $pdfPath])]);

            $this->log('certificate_issued', $certificate, [
                'certificate_number' => $certificateNumber,
                'registration_id' => $registration->id,
            ]);

            return $certificate->fresh();
        });
    }
}
