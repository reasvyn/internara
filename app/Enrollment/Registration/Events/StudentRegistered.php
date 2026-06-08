<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Events;

use App\Enrollment\Registration\Models\Registration;

final readonly class StudentRegistered
{
    public function __construct(
        public Registration $registration,
    ) {}
}