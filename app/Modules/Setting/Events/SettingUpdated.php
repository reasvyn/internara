<?php

declare(strict_types=1);

namespace App\Modules\Setting\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Setting\Data\SettingData;

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
