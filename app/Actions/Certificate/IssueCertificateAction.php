<?php

declare(strict_types=1);

namespace App\Actions\Certificate;

use App\Actions\Core\LogAuditAction;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Registration;
use App\Support\CertificateRenderer;
use Illuminate\Support\Facades\DB;

class IssueCertificateAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
        protected readonly CertificateRenderer $renderer,
    ) {}

    public function execute(Registration $registration, CertificateTemplate $template): Certificate
    {
        return DB::transaction(function () use ($registration, $template) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $registration->internship?->name ?? 'PKL'), 0, 6));

            $count = Certificate::whereYear('created_at', now()->year)->count() + 1;
            $certificateNumber = "{$prefix}/".now()->year.'/'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

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

            $this->logAudit->execute(
                action: 'certificate_issued',
                subjectType: Certificate::class,
                subjectId: $certificate->id,
                payload: ['certificate_number' => $certificateNumber, 'registration_id' => $registration->id],
                module: 'Certificate',
            );

            return $certificate->fresh();
        });
    }
}
