<?php

declare(strict_types=1);

namespace App\User\Entities;

use App\Core\Entities\BaseEntity;
use App\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class AdminEntity extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
        private bool $setupRequired,
    ) {}

    public static function fromModel(Model $model): static
    {
        $latestName =
            $model->status instanceof AccountStatus
                ? $model->status->value
                : $model->status ??
                    ($model->relationLoaded('statuses')
                        ? $model->statuses->last()?->name
                        : $model->latestStatus()?->name);

        return new self(
            status: AccountStatus::tryFrom((string) $latestName) ?? AccountStatus::PROVISIONED,
            isLocked: $model->getAttribute('locked_at') !== null,
            setupRequired: (bool) $model->getAttribute('setup_required'),
        );
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::SUSPENDED;
    }

    public function isArchived(): bool
    {
        return $this->status === AccountStatus::ARCHIVED;
    }

    public function isInactive(): bool
    {
        return $this->status === AccountStatus::INACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function requiresSetup(): bool
    {
        return $this->setupRequired;
    }

    public function canTransitionTo(AccountStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }
}
