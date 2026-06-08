<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Contracts\StatusEnum;

/**
 * @deprecated Use plain StatusEnum columns instead.
 *   Scheduled for removal in v2.0.
 *
 * Migration path:
 *   1. Add `status` string column casting to StatusEnum on the model
 *   2. Replace `setStatusEnum($x)` with `$model->status = $x`
 *   3. Replace `hasStatusEnum($x)` with `$model->status === $x`
 *   4. Replace `currentStatus()` with `$model->status`
 *   5. Remove the HasModelStatuses import and the trait usage
 */
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
