<?php

declare(strict_types=1);

namespace App\Domain\Document\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Document\Enums\DocumentCategory;
use App\Domain\Internship\Models\InternshipDocumentRequirement;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'slug', 'category', 'description', 'content', 'is_active'])]
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
            'category' => DocumentCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function getDownloadNameAttribute(): string
    {
        return $this->original_name ?? $this->name.'.pdf';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function internshipRequirements(): HasMany
    {
        return $this->hasMany(InternshipDocumentRequirement::class);
    }
}
