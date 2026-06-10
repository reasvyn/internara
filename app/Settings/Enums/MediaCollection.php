<?php

declare(strict_types=1);

namespace App\Settings\Enums;

enum MediaCollection: string
{
    case LOGO = 'brand_logo';
    case FAVICON = 'brand_favicon';
}
