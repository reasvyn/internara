<?php

declare(strict_types=1);

namespace App\Domain\Certification\Aggregates\Certificate\Models;

use App\Domain\Certification\Aggregates\Certificate\Enums\CertificateStatus;
use App\Domain\Core\Models\BaseModel;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\User\Models\User;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'certificate_number', 'template_id', 'status', 'issued_by', 'issued_at', 'metadata', 'revoked_by', 'revoked_at'])]
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
            'metadata' => 'array',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    protected static function newFactory(): CertificateFactory
    {
        return CertificateFactory::new();
    }
}
