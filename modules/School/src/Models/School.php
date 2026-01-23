<?php

declare(strict_types=1);

namespace Modules\School\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Modules\Department\Models\Concerns\HasDepartmentsRelation;
use Modules\Internship\Models\Concerns\HasInternshipsRelation;
use Modules\School\Database\Factories\SchoolFactory;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class School extends Model implements HasMedia
{
    use HasDepartmentsRelation;
    use HasFactory;
    use HasInternshipsRelation;
    use HasUuid;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'address', 'email', 'phone', 'fax', 'principal_name'];

    protected static function newFactory(): SchoolFactory
    {
        return SchoolFactory::new();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('school_logo')->singleFile();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('school_logo') ?: null;
    }

    /**
     * Set the school's logo.
     *
     * @param string|\Illuminate\Http\UploadedFile $file The logo file or path.
     * @param string $collectionName The media collection name.
     *
     * @return bool True if successful.
     */
    public function setLogo(string|UploadedFile $file, string $collectionName = 'school_logo'): bool
    {
        $this->clearMediaCollection($collectionName);

        return (bool) $this->addMedia($file)->toMediaCollection($collectionName);
    }
}
