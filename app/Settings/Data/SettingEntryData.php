<?php

declare(strict_types=1);

namespace App\Settings\Data;

use App\Core\Data\BaseData;

final readonly class SettingEntryData extends BaseData
{
    public function __construct(
        public string $key,
        public mixed $value,
        public ?string $group = null,
        public ?string $description = null,
        public ?string $type = null,
    ) {}
}
