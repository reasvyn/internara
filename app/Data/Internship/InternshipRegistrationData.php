<?php

declare(strict_types=1);

namespace App\Data\Internship;

/**
 * Data transfer object for student internship registration.
 */
final readonly class InternshipRegistrationData
{
    public function __construct(
        public string $internshipId,
        public ?string $placementId = null,
        public ?string $academicYear = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $proposedCompanyName = null,
        public ?string $proposedCompanyAddress = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            internshipId: $data['internship_id'],
            placementId: $data['placement_id'] ?? null,
            academicYear: $data['academic_year'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            proposedCompanyName: $data['proposed_company_name'] ?? null,
            proposedCompanyAddress: $data['proposed_company_address'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'internship_id' => $this->internshipId,
            'placement_id' => $this->placementId,
            'academic_year' => $this->academicYear,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'proposed_company_name' => $this->proposedCompanyName,
            'proposed_company_address' => $this->proposedCompanyAddress,
        ];
    }

    public function isDirectPlacement(): bool
    {
        return $this->placementId !== null;
    }

    public function isSelfProposed(): bool
    {
        return $this->proposedCompanyName !== null;
    }
}
