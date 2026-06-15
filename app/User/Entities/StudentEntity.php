<?php

declare(strict_types=1);

namespace App\User\Entities;

use App\Core\Entities\BaseEntity;
use App\User\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class StudentEntity extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
        private bool $setupRequired,
        private bool $hasActiveRegistration = false,
        private bool $hasPlacement = false,
    ) {}

    public static function fromModel(Model $model): static
    {
        $statusValue = $model->getAttribute('status');

        return new self(
            status: $statusValue instanceof AccountStatus ? $statusValue : AccountStatus::tryFrom((string) $statusValue) ?? AccountStatus::PROVISIONED,
            isLocked: $model->getAttribute('locked_at') !== null,
            setupRequired: (bool) $model->getAttribute('setup_required'),
            hasActiveRegistration: $model->relationLoaded('registrations')
                ? $model->registrations->contains(
                    fn ($r) => $r->asRegistrationState()->isActive(),
                )
                : false,
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

    public function isRegistered(): bool
    {
        return $this->hasActiveRegistration;
    }

    public function isPlaced(): bool
    {
        return $this->hasPlacement;
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
