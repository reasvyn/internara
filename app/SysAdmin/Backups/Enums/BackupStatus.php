<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Enums;

use App\Core\Contracts\StatusEnum;

enum BackupStatus: string implements StatusEnum
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return __('backups.status.'.$this->value);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED], true);
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! $target instanceof self) {
            return false;
        }

        return in_array($target, $this->validTransitions(), true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::RUNNING, self::FAILED],
            self::RUNNING => [self::COMPLETED, self::FAILED],
            self::COMPLETED => [],
            self::FAILED => [],
        };
    }

    public function isFinished(): bool
    {
        return $this->isTerminal();
    }
}
