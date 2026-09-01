<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Models;

use App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property string $id
 * @property \Carbon\Carbon $issued_at
 * @property \Carbon\Carbon $revoked_at
 * @property \App\Modules\Certification\Domain\Certificate\Enums\CertificateStatus $status
 * @property string $registration_id
 * @property string $certificate_number
 * @property string $qr_hash
 * @property string $template_content
 * @property string $issued_by
 * @property string $revoked_by
 * @property-read \App\Modules\Enrollment\Domain\Registration\Models\Registration|null $registration
 * @property-read \App\Modules\User\Models\User|null $issuer
 */

#[
    Fillable([
        'registration_id',
        'certificate_number',
        'qr_hash',
        'status',
        'template_content',
        'issued_by',
        'issued_at',
        'revoked_by',
        'revoked_at',
    ]),
]
class Certificate extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => CertificateStatus::ISSUED->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => CertificateStatus::class,
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    protected static function newFactory(): CertificateFactory
    {
        return CertificateFactory::new();
    }
}
