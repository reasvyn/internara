<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use App\Domain\Core\Contracts\StatusEnum;

trait HasModelStatuses
{
    public function setStatusEnum(StatusEnum $status): static
    {
        $this->setStatus($status->value);

        return $this;
    }

    public function hasStatusEnum(StatusEnum $status): bool
    {
        return $this->hasStatus($status->value);
    }

    public function currentStatus(): ?StatusEnum
    {
        $statusName = $this->status?->name;

        if ($statusName === null) {
            return null;
        }

        $enumClass = $this->statusEnumClass();

        return $enumClass::tryFrom($statusName);
    }

    protected function statusEnumClass(): string
    {
        return StatusEnum::class;
    }
}
