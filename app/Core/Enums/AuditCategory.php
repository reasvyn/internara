<?php

declare(strict_types=1);

namespace App\Core\Enums;

use App\Core\Contracts\LabelEnum;

enum AuditCategory: string implements LabelEnum
{
    case REQUIREMENTS = 'requirements';
    case PERMISSIONS = 'permissions';
    case DATABASE = 'database';
    case TERMINAL = 'terminal';
    case RECOMMENDATIONS = 'recommendations';

    public function label(): string
    {
        return __("setup.wizard.{$this->value}");
    }

    public function isCritical(): bool
    {
        return match ($this) {
            self::REQUIREMENTS, self::PERMISSIONS, self::DATABASE => true,
            self::TERMINAL, self::RECOMMENDATIONS => false,
        };
    }
}
