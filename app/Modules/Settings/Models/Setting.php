<?php

declare(strict_types=1);

namespace App\Modules\Settings\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Settings\Casts\SettingValueCast;
use App\Modules\Settings\Entities\SettingEntity;
use App\Modules\Settings\Enums\MediaCollection;
use App\Modules\Settings\Enums\SettingType;
use App\Modules\Settings\Observers\SettingObserver;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


/**
 * @property string $id
 * @property \App\Modules\Settings\Casts\SettingValueCast $value
 * @property string $key
 * @property string $type
 * @property string $description
 * @property string $group
 */

#[Fillable(['key', 'value', 'type', 'description', 'group'])]
class Setting extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::observe(SettingObserver::class);

        static::creating(function (Setting $setting): void {
            if ($setting->key === null || $setting->key === '') {
                throw new \RuntimeException(
                    'Setting model requires an explicit key. Use Setting::create([\'key\' => \'...\', ...]).',
                );
            }
        });
    }

    protected $casts = [
        'value' => SettingValueCast::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollection::LOGO->value)->singleFile();
        $this->addMediaCollection(MediaCollection::FAVICON->value)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(200)->format('webp');
    }

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    public function asSetting(): SettingEntity
    {
        return SettingEntity::fromModel($this);
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

    public function scopeOfType(Builder $query, SettingType|string $type): Builder
    {
        $value = $type instanceof SettingType ? $type->value : $type;

        return $query->where('type', $value);
    }

    public function scopeSearchable(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('key', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%");
        });
    }
}
