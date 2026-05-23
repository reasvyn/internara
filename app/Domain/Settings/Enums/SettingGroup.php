<?php

declare(strict_types=1);

namespace App\Domain\Settings\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum SettingGroup: string implements LabelEnum
{
    case GENERAL = 'general';
    case MAIL = 'mail';
    case SYSTEM = 'system';
    case BRANDING = 'branding';
    case FEATURES = 'features';
    case LOCALIZATION = 'localization';
    case NOTIFICATIONS = 'notifications';

    public function label(): string
    {
        return __('setting.groups.'.$this->value);
    }

    public static function default(): self
    {
        return self::GENERAL;
    }
}
