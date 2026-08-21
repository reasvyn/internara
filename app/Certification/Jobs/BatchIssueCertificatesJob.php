<?php

declare(strict_types=1);

namespace App\Certification\Jobs;

use App\Certification\Certificate\Actions\IssueCertificateAction;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class BatchIssueCertificatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [2, 10, 30];

    public function __construct(
        public readonly array $registrationIds,
        public readonly string $status,
        public readonly string $templateId,
        public readonly string $issuedBy,
    ) {}

    public function handle(IssueCertificateAction $issueCertificate): void
    {
        $template = CertificateTemplate::findOrFail($this->templateId);

        $registrations = Registration::query()
            ->whereIn('id', $this->registrationIds)
            ->where('status', $this->status)
            ->whereDoesntHave('certificates')
            ->get();

        Auth::setUser(User::findOrFail($this->issuedBy));

        try {
            foreach ($registrations as $registration) {
                $issueCertificate->execute($registration, $template);
            }
        } finally {
            Auth::forgetUser();
        }
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('Batch certificate issuance failed', [
            'registration_ids' => $this->registrationIds,
            'status' => $this->status,
            'template_id' => $this->templateId,
            'error' => $e->getMessage(),
        ]);
    }
}
