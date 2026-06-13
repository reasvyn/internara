<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Models;

use App\Core\Models\BaseModel;
use App\User\Models\User;
use Database\Factories\CertificateTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'name',
        'layout',
        'content_template',
        'is_active',
        'created_by',
    ]),
]
class CertificateTemplate extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): CertificateTemplateFactory
    {
        return CertificateTemplateFactory::new();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
