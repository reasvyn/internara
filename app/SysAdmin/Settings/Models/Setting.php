<?php

declare(strict_types=1);

namespace App\SysAdmin\Settings\Models;

use App\Core\Models\BaseModel;
use App\SysAdmin\Settings\Casts\SettingValueCast;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['key', 'value', 'type', 'description', 'group'])]
class Setting extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public const COLLECTION_LOGO = 'brand_logo';

    public const COLLECTION_FAVICON = 'brand_favicon';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_LOGO)->singleFile();
        $this->addMediaCollection(self::COLLECTION_FAVICON)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->format('webp');
    }

    public const VALID_TYPES = ['string', 'integer', 'float', 'boolean', 'json', 'encrypted', 'null'];

    protected $casts = [
        'value' => SettingValueCast::class,
    ];

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
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
}
