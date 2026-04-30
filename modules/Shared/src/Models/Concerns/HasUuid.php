<?php

declare(strict_types=1);

namespace Modules\Shared\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Trait HasUuid
 *
 * Automatically generates a UUID for the model's primary key if configured.
 * It also sets the incrementing and keyType properties appropriately.
 */
trait HasUuid
{
    /**
     * Determine if the model's primary key is incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Initialize the trait.
     */
    public function initializeHasUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    /**
     * Boot the trait.
     */
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if ($model->usesUuid() && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Determine if the model should use UUIDs.
     *
     * This can be overridden in the model to force UUID usage or based on config.
     */
    protected function usesUuid(): bool
    {
        return true;
    }
}
