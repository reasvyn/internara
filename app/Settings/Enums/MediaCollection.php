<?php

declare(strict_types=1);

namespace App\Settings\Enums;

use App\Core\Contracts\LabelEnum;

enum MediaCollection: string implements LabelEnum
{
    case LOGO = 'brand_logo';
    case FAVICON = 'brand_favicon';

    public function label(): string
    {
        return __('settings.media_collection.'.$this->value);
    }
}
