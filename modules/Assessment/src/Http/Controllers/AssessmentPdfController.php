<?php

declare(strict_types=1);

namespace Modules\Assessment\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Assessment\Services\Contracts\CertificateService;
use Modules\Internship\Services\Contracts\RegistrationService;

/**
 * Class AssessmentPdfController
 *
 * Handles PDF generation and download requests.
 */
class AssessmentPdfController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService,
        protected RegistrationService $registrationService,
        protected AssessmentService $assessmentService,
    ) {}

    /**
     * Download the certificate for a registration.
     */
    public function certificate(string $registrationId): Response
    {
        $registration = $this->registrationService->find($registrationId);

        // Basic Authorization check
        if (auth()->user()->cannot('view', $registration)) {
            abort(403);
        }

        // Completion Invariant: Must be ready
        $readiness = $this->assessmentService->getReadinessStatus($registrationId);
        if (!$readiness['is_ready']) {
            abort(403, __('assessment::messages.not_ready_for_credentials'));
        }

        $pdf = $this->certificateService->generateCertificate($registrationId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="certificate-' . $registration->student->username . '.pdf"',
            );
    }

    /**
     * Download the transcript for a registration.
     */
    public function transcript(string $registrationId): Response
    {
        $registration = $this->registrationService->find($registrationId);

        // Basic Authorization check
        if (auth()->user()->cannot('view', $registration)) {
            abort(403);
        }

        // Completion Invariant: Must be ready
        $readiness = $this->assessmentService->getReadinessStatus($registrationId);
        if (!$readiness['is_ready']) {
            abort(403, __('assessment::messages.not_ready_for_credentials'));
        }

        $pdf = $this->certificateService->generateTranscript($registrationId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="transcript-' . $registration->student->username . '.pdf"',
            );
    }

    /**
     * Verify a certificate (Signed Route).
     */
    public function verify(string $registrationId): Response
    {
        // This is a public page linked from QR Code
        $registration = $this->registrationService->find($registrationId);

        if (!$registration) {
            abort(404);
        }

        // Return a simple HTML verification page or the PDF itself
        // For this demo, let's return the PDF certificate as inline view
        $pdf = $this->certificateService->generateCertificate($registrationId);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'inline; filename="verify-' . $registrationId . '.pdf"',
            );
    }
}
