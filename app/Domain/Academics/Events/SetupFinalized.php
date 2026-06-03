<?php

declare(strict_types=1);

namespace App\Domain\Academics\Events;

final readonly class SetupFinalized
{
    public function __construct(
        public ?string $schoolId,
        public \DateTimeImmutable $installedAt,
    ) {}
}
