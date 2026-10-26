<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Data;

use App\Modules\Core\Data\BaseData;

/**
 * Verdict of the three issuance gates defined by FR-CERT-010.
 *
 * Produced by ReadCertificateEligibilityAction from live state — never cached,
 * so a newly finalized assessment or a fresh handbook acknowledgment is picked
 * up on the next issuance attempt.
 */
final readonly class CertificateEligibility extends BaseData
{
    public function __construct(
        public bool $assessmentCompleted,
        public bool $placementVerified,
        public bool $handbookAcknowledged,
    ) {}

    public function isEligible(): bool
    {
        return $this->assessmentCompleted
            && $this->placementVerified
            && $this->handbookAcknowledged;
    }

    /**
     * Gate keys the registration has not cleared, in the order FR-CERT-010 lists them.
     *
     * @return list<string>
     */
    public function unmetGates(): array
    {
        $gates = [];

        if (! $this->assessmentCompleted) {
            $gates[] = 'assessment';
        }

        if (! $this->placementVerified) {
            $gates[] = 'placement';
        }

        if (! $this->handbookAcknowledged) {
            $gates[] = 'handbook';
        }

        return $gates;
    }
}
