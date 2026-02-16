<?php

declare(strict_types=1);

namespace Modules\Internship\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Internship\Models\InternshipRequirement;
use Modules\Internship\Models\RequirementSubmission;

/**
 * Concern HasRequirements
 *
 * Provides functionality for models that require dynamic prerequisite verification.
 */
trait HasRequirements
{
    /**
     * Get all requirement submissions for this model.
     */
    public function requirementSubmissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class, 'registration_id');
    }

    /**
     * Determine if all mandatory requirements for the given academic year have been verified.
     */
    public function hasClearedAllMandatoryRequirements(?string $academicYear = null): bool
    {
        $year = $academicYear ?? $this->academic_year;

        if (! $year) {
            return false;
        }

        $mandatoryRequirements = InternshipRequirement::query()
            ->where('academic_year', $year)
            ->where('is_mandatory', true)
            ->where('is_active', true)
            ->get();

        if ($mandatoryRequirements->isEmpty()) {
            return true;
        }

        $verifiedSubmissionsCount = $this->requirementSubmissions()
            ->whereIn('requirement_id', $mandatoryRequirements->pluck('id'))
            ->where('status', \Modules\Internship\Enums\SubmissionStatus::VERIFIED)
            ->count();

        return $verifiedSubmissionsCount === $mandatoryRequirements->count();
    }

    /**
     * Get the requirement completion percentage.
     */
    public function getRequirementCompletionPercentage(?string $academicYear = null): float
    {
        $year = $academicYear ?? $this->academic_year;

        $totalActive = InternshipRequirement::query()
            ->where('academic_year', $year)
            ->where('is_active', true)
            ->count();

        if ($totalActive === 0) {
            return 100.0;
        }

        $verified = $this->requirementSubmissions()
            ->where('status', \Modules\Internship\Enums\SubmissionStatus::VERIFIED)
            ->count();

        return round(($verified / $totalActive) * 100, 2);
    }
}
