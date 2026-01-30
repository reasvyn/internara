<?php

declare(strict_types=1);

namespace Modules\Shared\Models\Concerns;

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
            return __('shared::status.unknown');
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
     * Define the translation prefix for status names.
     * Override this in the model if needed.
     */
    protected function getStatusTranslationPrefix(): string
    {
        return 'shared::status.';
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
