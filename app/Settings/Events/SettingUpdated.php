<?php

declare(strict_types=1);

namespace App\Settings\Events;

use App\Core\Events\BaseEvent;
use App\Settings\Data\SettingData;

final class SettingUpdated extends BaseEvent
{
    public function __construct(
        public SettingData $setting,
        public bool $wasRecentlyCreated,
    ) {}

    public function eventName(): string
    {
        return $this->wasRecentlyCreated ? 'setting.created' : 'setting.updated';
    }
}
