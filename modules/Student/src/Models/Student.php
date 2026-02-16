<?php

declare(strict_types=1);

namespace Modules\Student\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\Profile\Models\Concerns\HasProfileMorphRelation;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class Student
 *
 * Represents specific data for a Student.
 */
class Student extends Model implements HasMedia
{
    use HasFactory;
    use HasProfileMorphRelation;
    use HasUuid;
    use InteractsWithMedia;

    /**
     * Standard collection names for Student.
     */
    public const COLLECTION_PASSPORT_PHOTO = 'passport_photo';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['registration_number', 'national_identifier', 'class_name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_number' => 'encrypted',
            'national_identifier' => 'encrypted',
        ];
    }

    /**
     * Register the media collections for the student.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_PASSPORT_PHOTO)->singleFile();
    }

    /**
     * Register the media conversions for the student.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('avatar')
            ->fit(Fit::Crop, 300, 300)
            ->position(CropPosition::Top)
            ->performOnCollections(self::COLLECTION_PASSPORT_PHOTO);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Student\Database\Factories\StudentFactory
    {
        return \Modules\Student\Database\Factories\StudentFactory::new();
    }
}
