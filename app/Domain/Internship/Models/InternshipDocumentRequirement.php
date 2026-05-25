<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Document\Models\Document;
use App\Domain\Registration\Models\RegistrationDocument;
use Database\Factories\InternshipDocumentRequirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['internship_id', 'document_id', 'is_mandatory', 'sort_order'])]
class InternshipDocumentRequirement extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): InternshipDocumentRequirementFactory
    {
        return InternshipDocumentRequirementFactory::new();
    }

    protected $casts = [
        'is_mandatory' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function registrationDocuments(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'internship_document_requirement_id');
    }
}
