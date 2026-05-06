<?php

declare(strict_types=1);

namespace App\Domain\Core\Models;

use App\Casts\SettingValueCast;
use App\Traits\HasUuid;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * System setting model with typed value storage and key validation.
 *
 * S2 - Sustain: Centralized system configuration with proper type handling.
 */
#[Fillable(['key', 'value', 'type', 'description', 'group'])]
class Setting extends Model
{
    use HasFactory;
    use HasUuid;

    public const VALID_TYPES = ['string', 'integer', 'float', 'boolean', 'json', 'encrypted', 'null'];

    protected $casts = [
        'value' => SettingValueCast::class,
    ];

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->key === '') {
                Log::error('Attempted to save setting with empty key');

                throw new InvalidArgumentException('Setting key must not be empty.');
            }

            if (! preg_match('/^[a-z][a-z0-9_.]*$/', $model->key)) {
                Log::error('Attempted to save setting with invalid key format', [
                    'key' => $model->key,
                ]);

                throw new InvalidArgumentException(
                    "Setting key must be lowercase alphanumeric with underscores or dots. Got: {$model->key}",
                );
            }

            if ($model->type !== null && ! in_array($model->type, self::VALID_TYPES, true)) {
                Log::error('Attempted to save setting with invalid type', [
                    'key' => $model->key,
                    'type' => $model->type,
                ]);

                throw new InvalidArgumentException(
                    'Setting type must be one of: '.implode(', ', self::VALID_TYPES).". Got: {$model->type}",
                );
            }
        });
    }

    public function scopeGroup(Builder $query, string $name): Builder
    {
        return $query->where('group', $name);
    }

    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    public function scopeInGroup(Builder $query, array $groups): Builder
    {
        return $query->whereIn('group', $groups);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSearchable(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('key', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public static function groups(): Collection
    {
        return self::query()
            ->select('group')
            ->distinct()
            ->whereNotNull('group')
            ->orderBy('group')
            ->pluck('group');
    }

    public static function upsertBatch(array $settings): int
    {
        if ($settings === []) {
            return 0;
        }

        $updated = 0;

        foreach ($settings as $key => $attributes) {
            $value = is_array($attributes) ? ($attributes['value'] ?? null) : $attributes;
            $extra = is_array($attributes) ? array_diff_key($attributes, ['value' => null]) : [];

            $model = self::updateOrCreate(
                ['key' => $key],
                array_merge(['value' => $value], $extra),
            );

            if ($model->wasRecentlyCreated || $model->wasChanged()) {
                $updated++;
            }
        }

        return $updated;
    }
}
