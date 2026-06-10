<?php

declare(strict_types=1);

namespace App\Setup\SetupWizard\Events;

use App\Core\Events\BaseEvent;
use DateTimeImmutable;

final class SetupFinalized extends BaseEvent
{
    public function __construct(
        public ?string $departmentId,
        public DateTimeImmutable $installedAt,
    ) {}

    public function eventName(): string
    {
        return 'setup.finalized';
    }
}
