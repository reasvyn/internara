<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
