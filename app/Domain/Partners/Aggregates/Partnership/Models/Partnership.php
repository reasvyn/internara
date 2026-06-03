<?php

declare(strict_types=1);

namespace App\Domain\Partners\Aggregates\Partnership\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Partners\Aggregates\Partnership\Entities\PartnershipState;
use App\Domain\Partners\Aggregates\Partnership\Enums\PartnershipStatus;
use Database\Factories\PartnershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'company_id', 'agreement_number', 'title', 'start_date', 'end_date',
    'status', 'scope', 'contact_person_name', 'contact_person_phone',
    'contact_person_email', 'signed_by_school', 'signed_by_company',
    'signed_at', 'notes',
])]
class Partnership extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public const COLLECTION_MOU = 'mou_document';

    protected $attributes = [
        'status' => PartnershipStatus::ACTIVE->value,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_at' => 'date',
            'status' => PartnershipStatus::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_MOU)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->format('webp')
            ->nonQueued();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function asPartnershipState(): PartnershipState
    {
        return PartnershipState::fromModel($this);
    }

    protected static function newFactory(): PartnershipFactory
    {
        return PartnershipFactory::new();
    }
}
