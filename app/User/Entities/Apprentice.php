<?php

declare(strict_types=1);

namespace App\User\Entities;

use App\Core\Entities\BaseEntity;
use App\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class Apprentice extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
        private bool $setupRequired,
    ) {}

    public static function fromModel(Model $model): static
    {
        $statusValue = $model->getAttribute('status');

        return new self(
            status: AccountStatus::tryFrom((string) $statusValue) ?? AccountStatus::PROVISIONED,
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
