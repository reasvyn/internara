<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Assessment\Services\Contracts\CertificateService as Contract;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Shared\Services\BaseService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Class CertificateService
 *
 * Implements PDF generation logic for certificates and transcripts.
 */
class CertificateService extends BaseService implements Contract
{
    public function __construct(
        protected RegistrationService $registrationService,
        protected AssessmentService $assessmentService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function generateCertificate(string $registrationId): string
    {
        $registration = $this->registrationService->find($registrationId);
        $scoreCard = $this->assessmentService->getScoreCard($registrationId);
        $qrCode = $this->generateQrCode($registrationId);

        $pdf = Pdf::loadView('assessment::pdf.certificate', [
            'registration' => $registration,
            'student' => $registration->student,
            'scoreCard' => $scoreCard,
            'qrCode' => $qrCode,
            'date' => now()->translatedFormat('d F Y'),
        ]);

        return $pdf->output();
    }

    /**
     * {@inheritDoc}
     */
    public function generateTranscript(string $registrationId): string
    {
        $registration = $this->registrationService->find($registrationId);
        $scoreCard = $this->assessmentService->getScoreCard($registrationId);

        $pdf = Pdf::loadView('assessment::pdf.transcript', [
            'registration' => $registration,
            'student' => $registration->student,
            'scoreCard' => $scoreCard,
            'date' => now()->translatedFormat('d F Y'),
        ]);

        return $pdf->output();
    }

    /**
     * {@inheritDoc}
     */
    public function getVerificationUrl(string $registrationId): string
    {
        return URL::signedRoute('assessment.verify', ['registration' => $registrationId]);
    }

    /**
     * Generate a base64 encoded QR code for verification.
     */
    protected function generateQrCode(string $registrationId): string
    {
        $url = $this->getVerificationUrl($registrationId);

        return base64_encode((string) QrCode::format('svg')->size(150)->margin(1)->generate($url));
    }
}
