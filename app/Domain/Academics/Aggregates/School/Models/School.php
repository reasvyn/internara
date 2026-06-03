<?php

declare(strict_types=1);

namespace App\Domain\Academics\Aggregates\School\Models;

use App\Domain\Academics\Aggregates\School\Entities\SchoolState;
use App\Domain\Core\Models\BaseModel;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['institutional_code', 'name', 'address', 'email', 'phone', 'fax', 'principal_name', 'website'])]
#[Appends(['logo_url'])]
class School extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public const COLLECTION_LOGO = 'logo';

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function internships(): HasMany
    {
        return $this->hasManyThrough(Internship::class, Department::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_LOGO)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->format('webp')
            ->nonQueued();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::COLLECTION_LOGO) ?: null;
    }

    public function asSchoolState(): SchoolState
    {
        return SchoolState::fromModel($this);
    }

    protected static function newFactory(): SchoolFactory
    {
        return SchoolFactory::new();
    }
}
