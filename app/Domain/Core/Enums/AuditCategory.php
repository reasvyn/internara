<?php

declare(strict_types=1);

namespace App\Domain\Core\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum AuditCategory: string implements LabelEnum
{
    case Requirements = 'requirements';
    case Permissions = 'permissions';
    case Database = 'database';
    case Terminal = 'terminal';
    case Recommendations = 'recommendations';

    public function label(): string
    {
        return __("setup.wizard.{$this->value}");
    }

    public function isCritical(): bool
    {
        return match ($this) {
            self::Requirements, self::Permissions, self::Database => true,
            self::Terminal, self::Recommendations => false,
        };
    }
}
