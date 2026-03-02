<?php

declare(strict_types=1);

namespace Modules\School\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Modules\Department\Models\Concerns\HasDepartmentsRelation;
use Modules\Internship\Models\Concerns\HasInternshipsRelation;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\School\Database\Factories\SchoolFactory;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;

class School extends Model implements HasMedia
{
    use HasDepartmentsRelation;
    use HasFactory;
    use HasInternshipsRelation;
    use HasUuid;
    use InteractsWithMedia;
    use LogsActivity;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['logo_url'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['institutional_code', 'name', 'address', 'email', 'phone', 'fax', 'principal_name'];

    /**
     * Configure activity logging for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'fax' => 'encrypted',
            'principal_name' => 'encrypted',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SchoolFactory
    {
        return SchoolFactory::new();
    }

    /**
     * Register the media collections for the school's logo.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_LOGO)->singleFile();
    }

    /**
     * Legacy accessor for logo_url to support appends and toArray().
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::COLLECTION_LOGO) ?: null;
    }

    /**
     * Set the school's logo.
     *
     * @param string|\Illuminate\Http\UploadedFile $file The logo file or path.
     * @param string $collectionName The media collection name.
     *
     * @return bool True if successful.
     */
    public function setLogo(
        string|UploadedFile $file,
        string $collectionName = self::COLLECTION_LOGO,
    ): bool {
        return $this->setMedia($file, $collectionName);
    }
}
