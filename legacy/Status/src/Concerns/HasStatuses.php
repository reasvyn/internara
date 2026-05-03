<?php

declare(strict_types=1);

namespace Modules\Status\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Modules\Status\Enums\Status as StatusEnum;
use Spatie\ModelStatus\HasStatuses as SpatieHasStatuses;

/**
 * Trait HasStatuses
 *
 * Provides a standardized way to manage statuses for Eloquent models
 * using the spatie/laravel-model-status package.
 */
trait HasStatuses
{
    use SpatieHasStatuses;

    /**
     * Override the statuses relationship to use created_at for ordering.
     * The base package uses latest('id') which is incompatible with UUIDs.
     */
    public function statuses(): MorphMany
    {
        return $this->morphMany(
            config('model-status.status_model'),
            'model',
            'model_type',
            config('model-status.model_primary_key_attribute', 'model_id'),
        )->latest();
    }

    /**
     * Standard status names.
     */
    public const STATUS_PENDING = StatusEnum::PENDING->value;

    public const STATUS_ACTIVATED = StatusEnum::ACTIVATED->value;

    public const STATUS_VERIFIED = StatusEnum::VERIFIED->value;

    public const STATUS_PROTECTED = StatusEnum::PROTECTED->value;

    public const STATUS_RESTRICTED = StatusEnum::RESTRICTED->value;

    public const STATUS_SUSPENDED = StatusEnum::SUSPENDED->value;

    public const STATUS_INACTIVE = StatusEnum::INACTIVE->value;

    public const STATUS_ARCHIVED = StatusEnum::ARCHIVED->value;

    /**
     * Default fallback color for unknown statuses.
     */
    protected const DEFAULT_STATUS_COLOR = 'gray';

    /**
     * Get the current status as an Enum instance.
     */
    public function getStatus(): ?StatusEnum
    {
        $status = $this->latestStatus();

        return $status ? StatusEnum::tryFrom($status->name) : null;
    }

    /**
     * Get the label for the current status.
     *
     * This can be used to return a translated or human-readable label.
     */
    public function getStatusLabel(): string
    {
        $status = $this->getStatus();

        if (! $status) {
            return __('status::status.unknown');
        }

        return __($status->label());
    }

    /**
     * Get the CSS class or color associated with the current status.
     */
    public function getStatusColor(): string
    {
        $status = $this->getStatus();

        if (! $status) {
            return self::DEFAULT_STATUS_COLOR;
        }

        return $status->color();
    }

    /**
     * Check if the current model status is expired.
     */
    public function isStatusExpired(): bool
    {
        return $this->latestStatus()?->isExpired() ?? false;
    }

    /**
     * Check if the current model status matches a given status.
     */
    public function hasStatus(StatusEnum|string $status): bool
    {
        $current = $this->getStatus();

        if ($status instanceof StatusEnum) {
            return $current === $status;
        }

        return $current?->value === $status;
    }

    /**
     * Determine if the model is in an active/usable state.
     */
    public function isActive(): bool
    {
        return $this->getStatus()?->isActive() ?? false;
    }

    /**
     * Determine if the model has a "Problem" status (Restricted/Suspended).
     */
    public function isProblem(): bool
    {
        return $this->getStatus()?->isProblem() ?? false;
    }

    /**
     * Lifecycle status checkers.
     */
    public function isPending(): bool
    {
        return $this->hasStatus(StatusEnum::PENDING);
    }

    public function isActivated(): bool
    {
        return $this->hasStatus(StatusEnum::ACTIVATED);
    }

    public function isVerified(): bool
    {
        return $this->hasStatus(StatusEnum::VERIFIED);
    }

    public function isProtected(): bool
    {
        return $this->hasStatus(StatusEnum::PROTECTED);
    }

    public function isRestricted(): bool
    {
        return $this->hasStatus(StatusEnum::RESTRICTED);
    }

    public function isSuspended(): bool
    {
        return $this->hasStatus(StatusEnum::SUSPENDED);
    }

    public function isInactive(): bool
    {
        return $this->hasStatus(StatusEnum::INACTIVE);
    }

    public function isArchived(): bool
    {
        return $this->hasStatus(StatusEnum::ARCHIVED);
    }

    /**
     * Check if can transition to another status.
     */
    public function canTransitionTo(StatusEnum $target): bool
    {
        return $this->getStatus()?->canTransitionTo($target) ?? false;
    }

    /**
     * Get the timestamp of the last status change.
     */
    public function getLastStatusChangeAt(): ?Carbon
    {
        return $this->latestStatus()?->created_at;
    }

    /**
     * Force refresh the status relationship cache.
     */
    public function refreshStatus(): self
    {
        return $this->unsetRelation('statuses');
    }
}
