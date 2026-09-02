<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Partners\Domain\Partnership\Entities\PartnershipState;
use App\Modules\Partners\Domain\Partnership\Enums\PartnershipStatus;
use Carbon\Carbon;
use Database\Factories\PartnershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $signed_at
 * @property PartnershipStatus $status
 * @property string $company_id
 * @property string $agreement_number
 * @property string $title
 * @property string $scope
 * @property string $contact_person_name
 * @property string $contact_person_phone
 * @property string $contact_person_email
 * @property string $signed_by_school
 * @property string $signed_by_company
 * @property string $notes
 * @property-read Company|null $company
 */
#[
    Fillable([
        'company_id',
        'agreement_number',
        'title',
        'start_date',
        'end_date',
        'status',
        'scope',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_email',
        'signed_by_school',
        'signed_by_company',
        'signed_at',
        'notes',
    ]),
]
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
        $this->addMediaConversion('thumb')->width(400)->format('webp')->nonQueued();
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
