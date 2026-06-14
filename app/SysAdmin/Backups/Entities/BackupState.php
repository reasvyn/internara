<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Entities;

use App\Core\Entities\BaseEntity;
use App\SysAdmin\Backups\Enums\BackupStatus;
use App\SysAdmin\Backups\Enums\BackupType;
use Illuminate\Database\Eloquent\Model;

final readonly class BackupState extends BaseEntity
{
    public function __construct(
        private string $status,
        private string $type,
        private int $fileSize,
        private ?string $errorOutput,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
            type: $model->type,
            fileSize: (int) $model->file_size,
            errorOutput: $model->error_output,
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === BackupStatus::COMPLETED->value;
    }

    public function isFailed(): bool
    {
        return $this->status === BackupStatus::FAILED->value;
    }

    public function isDeletable(): bool
    {
        return $this->isCompleted() || $this->isFailed();
    }

    public function formattedSize(): string
    {
        $bytes = $this->fileSize;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function type(): BackupType
    {
        return BackupType::from($this->type);
    }
}
