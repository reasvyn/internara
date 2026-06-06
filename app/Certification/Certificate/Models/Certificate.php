<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Models;

use App\Certification\Certificate\Enums\CertificateStatus;
use App\Core\Models\BaseModel;
use App\Enrollment\Models\Registration;
use App\User\Models\User;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'certificate_number', 'qr_hash', 'status', 'template_content', 'issued_by', 'issued_at'])]
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
