<?php

declare(strict_types=1);

namespace App\Data\Internship;

/**
 * Data transfer object for direct administrative placement.
 */
final readonly class DirectPlacementData
{
    public function __construct(
        public string $placementId,
        public ?string $academicYear = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $teacherId = null,
        public ?string $mentorId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            placementId: $data['placement_id'],
            academicYear: $data['academic_year'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            teacherId: $data['teacher_id'] ?? null,
            mentorId: $data['mentor_id'] ?? null,
        );
    }
}
