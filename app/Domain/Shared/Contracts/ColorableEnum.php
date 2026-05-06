<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Contract for status enums that support color/badge variants.
 *
 * Implemented by lifecycle status enums across domains (AttendanceStatus,
 * SubmissionStatus, AbsenceRequestStatus, etc.).
 */
interface ColorableEnum
{
    public function color(): string;
}
