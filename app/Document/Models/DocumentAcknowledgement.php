<?php

declare(strict_types=1);

namespace App\Document\Models;

use App\Core\Models\BaseModel;
use App\User\Models\User;
use Database\Factories\DocumentAcknowledgementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'document_id', 'acknowledged_at', 'ip_address'])]
class DocumentAcknowledgement extends BaseModel
{
    use HasFactory;

    protected $table = 'document_acknowledgements';

    protected static function newFactory(): DocumentAcknowledgementFactory
    {
        return DocumentAcknowledgementFactory::new();
    }

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
