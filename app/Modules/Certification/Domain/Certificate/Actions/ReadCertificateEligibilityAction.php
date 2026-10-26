<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Data\CertificateEligibility;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * FR-CERT-010 — the three gates a graduate must clear before a certificate exists.
 *
 * Reads live state through the owning modules — the Assessment table for
 * completion, the registration's placement bridge, and the handbook
 * acknowledgment trail — rather than a cached eligibility flag that could
 * stale between a revised score and the next issuance attempt.
 */
final class ReadCertificateEligibilityAction extends BaseReadAction
{
    public function execute(Registration $registration): CertificateEligibility
    {
        return new CertificateEligibility(
            assessmentCompleted: $this->hasFinalizedAssessment($registration),
            placementVerified: $registration->placement_id !== null,
            handbookAcknowledged: $this->hasAcknowledgedCurrentHandbook($registration),
        );
    }

    /**
     * Gate 1 — an assessment must exist and be finalized.
     *
     * An unfinalized assessment means the result is still being negotiated, so
     * there is no grade to attest to yet.
     */
    private function hasFinalizedAssessment(Registration $registration): bool
    {
        return $registration->assessments()
            ->whereNotNull('finalized_at')
            ->exists();
    }

    /**
     * Gate 3 — the active handbook aimed at the student must be acknowledged at
     * its current version.
     *
     * A school that published no handbook has nothing to acknowledge, so the
     * gate passes instead of blocking every issuance. Acknowledgment lives in
     * the activity trail (written by AcknowledgeHandbookAction), which carries
     * the version the student accepted — so a handbook revision re-opens the
     * gate until the new version is acknowledged.
     */
    private function hasAcknowledgedCurrentHandbook(Registration $registration): bool
    {
        $student = $registration->student;

        if (! $student instanceof User) {
            return false;
        }

        $handbook = Document::query()
            ->ofType(DocumentCategory::HANDBOOK->value)
            ->active()
            ->get()
            ->first(
                fn (Document $document): bool => $document->asHandbook()->isTargetedAt($student),
            );

        if (! $handbook instanceof Document) {
            return true;
        }

        $acknowledgment = Activity::causedBy($student)
            ->forEvent('acknowledged')
            ->where('subject_id', $handbook->id)
            ->where('subject_type', Document::class)
            ->latest()
            ->first();

        return ! $handbook->asHandbook()->isNewerThan($acknowledgment);
    }
}
