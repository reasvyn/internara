<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backup\Enums;

use App\Modules\Core\Contracts\LabelEnum;

enum BackupType: string implements LabelEnum
{
    case DATABASE = 'database';
    case STORAGE = 'storage';
    case BOTH = 'both';

    public function label(): string
    {
        return __('backup.type.'.$this->value);
    }
}
