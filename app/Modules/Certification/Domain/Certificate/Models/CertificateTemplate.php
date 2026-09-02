<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\User\Models\User;
use Database\Factories\CertificateTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property bool $is_active
 * @property string $name
 * @property string $layout
 * @property string $content_template
 * @property string $created_by
 * @property-read User|null $createdBy
 */
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
