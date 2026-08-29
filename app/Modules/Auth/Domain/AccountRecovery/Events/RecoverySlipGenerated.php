<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\AccountRecovery\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Models\User;

final class RecoverySlipGenerated extends BaseEvent
{
    public function __construct(
        public readonly User $user,
        public readonly int $codeCount,
    ) {}

    public function eventName(): string
    {
        return 'auth.recovery_slip_generated';
    }
}
