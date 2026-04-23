<?php

declare(strict_types=1);

namespace Modules\Status\Concerns;

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
    public function statuses(): \Illuminate\Database\Eloquent\Relations\MorphMany
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

        if (!$status) {
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

        if (!$status) {
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
     * Force refresh the status relationship cache.
     */
    public function refreshStatus(): self
    {
        return $this->unsetRelation('statuses');
    }
}
