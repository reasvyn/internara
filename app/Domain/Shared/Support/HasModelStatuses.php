<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use App\Domain\Core\Contracts\StatusEnum;
use Spatie\ModelStatus\HasStatuses;

/**
 * Bridges Spatie's HasStatuses with typed StatusEnum objects.
 *
 * Usage:
 *   class YourModel extends BaseModel
 *   {
 *       use HasModelStatuses;
 *
 *       protected static function statusEnumClass(): string
 *       {
 *           return YourStatusEnum::class;
 *       }
 *   }
 *
 * Then:
 *   $model->setStatusEnum(YourStatusEnum::ACTIVE);
 *   $model->hasStatusEnum(YourStatusEnum::ACTIVE);
 *   $model->currentStatus(); // ?YourStatusEnum
 */
trait HasModelStatuses
{
    use HasStatuses;

    /**
     * FQCN of the StatusEnum backed by this model.
     *
     * @return class-string<StatusEnum>
     */
    abstract protected static function statusEnumClass(): string;

    /**
     * Set the model's status using a typed StatusEnum.
     */
    public function setStatusEnum(StatusEnum $status, ?string $reason = null): static
    {
        $this->setStatus($status->value, $reason);

        return $this;
    }

    /**
     * Check if the model currently has the given status.
     */
    public function hasStatusEnum(StatusEnum $status): bool
    {
        return $this->hasStatus($status->value);
    }

    /**
     * Get the current status as a typed StatusEnum instance.
     */
    public function currentStatus(): ?StatusEnum
    {
        $latest = $this->latestStatus();

        if ($latest === null) {
            return null;
        }

        /** @var class-string<StatusEnum> $enumClass */
        $enumClass = static::statusEnumClass();

        return $enumClass::tryFrom($latest->name);
    }
}
