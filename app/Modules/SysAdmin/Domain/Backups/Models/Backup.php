<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backups\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\SysAdmin\Domain\Backups\Entities\BackupState;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $file_size
 * @property array $metadata
 * @property Carbon $started_at
 * @property Carbon $completed_at
 * @property string $type
 * @property string $file_path
 * @property string $status
 * @property string $error_output
 * @property string $created_by
 * @property-read User|null $creator
 */
#[Fillable([
    'type',
    'file_path',
    'file_size',
    'status',
    'metadata',
    'error_output',
    'created_by',
    'started_at',
    'completed_at',
])]
class Backup extends BaseModel
{
    use HasFactory;

    protected $table = 'backups';

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BackupFactory
    {
        return BackupFactory::new();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function asBackupState(): BackupState
    {
        return BackupState::fromModel($this);
    }
}
