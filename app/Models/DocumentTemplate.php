<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Templates for generating formal letters and documents.
 */
class DocumentTemplate extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'content',
        'is_active',
        'category',
    ];

    protected $casts = [
        'category' => DocumentCategory::class,
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(OfficialDocument::class, 'template_id');
    }
}
