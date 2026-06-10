<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Events;

use App\Core\Events\BaseEvent;
use App\Enrollment\Registration\Models\Registration;

final class StudentRegistered extends BaseEvent
{
    public function __construct(public Registration $registration) {}

    public function eventName(): string
    {
        return 'student.registered';
    }
}
