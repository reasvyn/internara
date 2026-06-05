<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\GdprDeletionLog\Models;

use App\Core\Models\BaseModel;
use App\User\Models\User;
use Database\Factories\GdprDeletionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'user_email', 'deletion_type', 'reason', 'deleted_by', 'metadata', 'deleted_at'])]
class GdprDeletionLog extends BaseModel
{
    use HasFactory;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $casts = [
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    protected static function newFactory(): GdprDeletionLogFactory
    {
        return GdprDeletionLogFactory::new();
    }
}
