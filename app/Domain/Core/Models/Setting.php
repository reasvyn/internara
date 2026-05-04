<?php

declare(strict_types=1);

namespace App\Domain\Core\Models;

use App\Domain\Core\Casts\SettingValueCast;
use App\Domain\Core\Concerns\HasUuid;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * System setting model with typed value storage.
 *
 * S2 - Sustain: Centralized system configuration with proper type handling.
 */
#[Fillable(['key', 'value', 'type', 'description', 'group'])]
class Setting extends Model
{
    use HasFactory;
    use HasUuid;

    protected $casts = [
        'value' => SettingValueCast::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    /**
     * Boot the model and add validation.
     */
    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->key === '') {
                Log::error('Attempted to save setting with empty key');

                throw new InvalidArgumentException('Setting key must not be empty.');
            }
        });
    }

    /**
     * Scope a query to only include settings belonging to a given group.
     */
    public function scopeGroup(Builder $query, string $name): Builder
    {
        return $query->where('group', $name);
    }

    /**
     * Scope a query to find a setting by its key.
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }
}
