<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Enrollment\Enums\RegistrationDocumentStatus;
use App\Domain\User\Models\User;
use Database\Factories\RegistrationDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['registration_id', 'internship_document_requirement_id', 'status', 'admin_notes', 'verified_by', 'verified_at'])]
class RegistrationDocument extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $casts = [
        'status' => RegistrationDocumentStatus::class,
        'verified_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(InternshipDocumentRequirement::class, 'internship_document_requirement_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    protected static function newFactory(): RegistrationDocumentFactory
    {
        return RegistrationDocumentFactory::new();
    }
}
