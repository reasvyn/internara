<?php

declare(strict_types=1);

namespace Modules\Status\Concerns;

use Spatie\ModelStatus\HasStatuses;

/**
 * Trait HasStatus
 *
 * Provides a standardized way to manage statuses for Eloquent models
 * using the spatie/laravel-model-status package.
 */
trait HasStatus
{
    use HasStatuses;

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
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING = 'pending';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * Default fallback color for unknown statuses.
     */
    protected const DEFAULT_STATUS_COLOR = 'gray';

    /**
     * Get the label for the current status.
     *
     * This can be used to return a translated or human-readable label.
     */
    public function getStatusLabel(): string
    {
        $status = $this->latestStatus();

        if (! $status) {
            return __('status::status.unknown');
        }

        return __($this->getStatusTranslationPrefix().$status->name);
    }

    /**
     * Get the CSS class or color associated with the current status.
     */
    public function getStatusColor(): string
    {
        $status = $this->latestStatus();

        if (! $status) {
            return self::DEFAULT_STATUS_COLOR;
        }

        return $this->getStatusColorMap()[$status->name] ?? self::DEFAULT_STATUS_COLOR;
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

    /**
     * Define the translation prefix for status names.
     * Override this in the model if needed.
     */
    protected function getStatusTranslationPrefix(): string
    {
        return 'status::status.';
    }

    /**
     * Define the color map for statuses.
     * Override this in the model.
     *
     * @return array<string, string>
     */
    protected function getStatusColorMap(): array
    {
        return [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_INACTIVE => 'error',
        ];
    }
}
