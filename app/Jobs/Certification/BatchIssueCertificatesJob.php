<?php

declare(strict_types=1);

namespace App\Jobs\Certification;

use App\Certification\Certificate\Actions\IssueCertificateAction;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BatchIssueCertificatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [2, 10, 30];

    public function __construct(
        protected readonly array $studentIds,
        protected readonly string $templateId,
        protected readonly string $issuedBy,
    ) {}

    public function handle(IssueCertificateAction $issueCertificate): void
    {
        $template = CertificateTemplate::findOrFail($this->templateId);

        $registrations = Registration::query()
            ->whereIn('student_id', $this->studentIds)
            ->get();

        foreach ($registrations as $registration) {
            $issueCertificate->execute($registration, $template);
        }
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('Batch certificate issuance failed', [
            'student_ids' => $this->studentIds,
            'template_id' => $this->templateId,
            'error' => $e->getMessage(),
        ]);
    }
}
