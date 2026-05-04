<?php

declare(strict_types=1);

namespace App\Domain\Document\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStatus\HasStatuses;

/**
 * An official document (Letter, Certificate, Report, Permit).
 */
#[Fillable(['template_id', 'documentable_id', 'documentable_type', 'title', 'document_number', 'issued_at', 'expires_at', 'metadata'])]
class OfficialDocument extends Model implements HasMedia
{
    use HasFactory, HasStatuses, HasUuid, InteractsWithMedia;

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * Get the owning documentable model.
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }
}
