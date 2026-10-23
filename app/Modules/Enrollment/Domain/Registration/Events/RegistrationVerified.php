<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class RegistrationVerified extends BaseEvent
{
    public function __construct(
        public Registration $registration,
    ) {}

    public function eventName(): string
    {
        return 'registration.verified';
    }
}
