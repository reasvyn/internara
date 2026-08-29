<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backups\Enums;

use App\Modules\Core\Contracts\LabelEnum;

enum BackupType: string implements LabelEnum
{
    case DATABASE = 'database';
    case STORAGE = 'storage';
    case BOTH = 'both';

    public function label(): string
    {
        return __('backups.type.'.$this->value);
    }
}
