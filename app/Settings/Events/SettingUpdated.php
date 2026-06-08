<?php

declare(strict_types=1);

namespace App\Settings\Events;

use App\Settings\Data\SettingData;

final readonly class SettingUpdated
{
    public function __construct(
        public SettingData $setting,
        public bool $wasRecentlyCreated,
    ) {}
}