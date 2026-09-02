<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Models;

use App\Modules\Core\Models\BaseModel;
use Database\Factories\GdprDeletionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property array $metadata_snapshot
 * @property string $user_id
 */
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
