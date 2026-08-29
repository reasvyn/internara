<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\AccountApplication\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;

final class AccountApplicationRejected extends BaseEvent
{
    public function __construct(public AccountApplication $application) {}

    public function eventName(): string
    {
        return 'account_application.rejected';
    }
}
