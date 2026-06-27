<?php

declare(strict_types=1);

namespace App\Enrollment\AccountApplication\Events;

use App\Core\Events\BaseEvent;
use App\Enrollment\AccountApplication\Models\AccountApplication;

final class AccountApplicationApproved extends BaseEvent
{
    public function __construct(public AccountApplication $application) {}

    public function eventName(): string
    {
        return 'account_application.approved';
    }
}
