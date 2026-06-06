<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\GdprDeletionLog\Models;

use App\Core\Models\BaseModel;
use Database\Factories\GdprDeletionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['user_id', 'metadata_snapshot'])]
class GdprDeletionLog extends BaseModel
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $casts = [
        'metadata_snapshot' => 'array',
    ];

    protected static function newFactory(): GdprDeletionLogFactory
    {
        return GdprDeletionLogFactory::new();
    }
}
