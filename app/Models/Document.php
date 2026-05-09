<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Document\DocumentCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'slug', 'category', 'description', 'content', 'is_active'])]
class Document extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $casts = [
        'category' => DocumentCategory::class,
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function fileUrl(): ?string
    {
        return $this->getFirstMediaUrl('file');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
