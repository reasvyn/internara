<?php

declare(strict_types=1);

namespace App\Domain\Core\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasUuid
 *
 * Automatically generates a UUID v4 for the model's primary key.
 *
 * Usage: Apply to any Eloquent model that requires a UUID primary key.
 * The trait disables auto-incrementing and sets the key type to string.
 */
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
