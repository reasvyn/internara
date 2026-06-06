<?php

declare(strict_types=1);

namespace App\Setup\Events;

final readonly class SetupFinalized
{
    public function __construct(
        public ?string $departmentId,
        public \DateTimeImmutable $installedAt,
    ) {}
}
