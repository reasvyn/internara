<?php

declare(strict_types=1);

namespace App\Domain\User\Entities;

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final readonly class Apprentice extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
        private bool $setupRequired,
    ) {}

    public static function fromModel(Model $model): static
    {
        $statuses = $model->getRelationValue('statuses');
        $latestName = $statuses instanceof Collection ? $statuses->last()?->name : null;

        return new self(
            status: AccountStatus::tryFrom($latestName ?? '') ?? AccountStatus::PROVISIONED,
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
