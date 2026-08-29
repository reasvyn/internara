<?php

declare(strict_types=1);

namespace App\Modules\Settings\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Settings\Data\SettingData;

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
