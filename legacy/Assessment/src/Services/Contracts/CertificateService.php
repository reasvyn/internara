<?php

declare(strict_types=1);

namespace Modules\Assessment\Services\Contracts;

/**
 * Interface CertificateService
 *
 * Handles the generation and verification of internship certificates and transcripts.
 */
interface CertificateService
{
    /**
     * Generate a PDF certificate for a specific registration.
     *
     * @return string Raw PDF binary data.
     */
    public function generateCertificate(string $registrationId): string;

    /**
     * Generate a PDF transcript for a specific registration.
     *
     * @return string Raw PDF binary data.
     */
    public function generateTranscript(string $registrationId): string;

    /**
     * Generate a verification URL for a registration.
     */
    public function getVerificationUrl(string $registrationId): string;
}
