<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Models;

use App\Core\Models\BaseModel;
use App\SysAdmin\Backups\Entities\BackupState;
use App\User\Models\User;
use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
