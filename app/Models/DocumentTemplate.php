<?php

declare(strict_types=1);

namespace App\Models;

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
        'content', // Blade/Markdown content with placeholders
        'is_active',
        'category', // e.g., 'application', 'permit', 'certificate'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(FormalDocument::class, 'template_id');
    }
}
