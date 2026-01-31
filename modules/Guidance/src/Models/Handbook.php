<?php

declare(strict_types=1);

namespace Modules\Guidance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\MediaLibrary\HasMedia;

class Handbook extends Model implements HasMedia
{
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;

    protected $fillable = ['title', 'description', 'version', 'is_active', 'is_mandatory'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    /**
     * Define media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')->singleFile()->useDisk('private');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Guidance\Database\Factories\HandbookFactory
    {
        return \Modules\Guidance\Database\Factories\HandbookFactory::new();
    }
}
