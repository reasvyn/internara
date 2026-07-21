<?php

declare(strict_types=1);

namespace App\Document\Models;

use App\Core\Models\BaseModel;
use App\Document\Handbook\Entities\HandbookEntity;
use App\User\Models\User;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[
    Fillable([
        'type',
        'slug',
        'title',
        'content',
        'file_path',
        'version',
        'is_active',
        'metadata',
        'created_by',
    ]),
]
class Document extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'version' => 'integer',
            'metadata' => 'json',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDownloadNameAttribute(): string
    {
        return $this->original_name ?? $this->title.'.pdf';
    }

    public function asHandbook(): HandbookEntity
    {
        return HandbookEntity::fromModel($this);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
        $this->addMediaCollection('handbook_file')->singleFile();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
