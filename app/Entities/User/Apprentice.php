<?php

declare(strict_types=1);

namespace App\Entities\User;

use App\Entities\BaseEntity;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pure business rules for a user/apprentice account.
 *
 * No Eloquent, no framework dependencies — only domain logic.
 */
final readonly class Apprentice extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
        private bool $setupRequired,
    ) {}

    public static function fromModel(Model $model): static
    {
        assert($model instanceof User);

        return new self(
            status: AccountStatus::tryFrom($model->latestStatus()?->name ?? '') ?? AccountStatus::PROVISIONED,
            isLocked: $model->locked_at !== null,
            setupRequired: (bool) $model->setup_required,
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
