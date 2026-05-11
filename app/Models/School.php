<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\School\SchoolState;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::COLLECTION_LOGO) ?: null;
    }

    public function schoolSingleRecordEnabled(): bool
    {
        return (bool) config('school.single_record', true);
    }

    public function schoolRecordExists(): bool
    {
        return static::query()->exists();
    }

    public function asSchoolState(): SchoolState
    {
        return SchoolState::fromModel($this);
    }
}
