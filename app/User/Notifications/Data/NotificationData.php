<?php

declare(strict_types=1);

namespace App\User\Notifications\Data;

use App\Core\Data\BaseData;

final readonly class NotificationData extends BaseData
{
    public function __construct(
        public string $userId,
        public string $type,
        public string $title,
        public ?string $message = null,
        public ?array $data = null,
        public ?string $link = null,
    ) {}
}