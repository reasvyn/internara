<?php

declare(strict_types=1);

namespace App\Entities\Notification;

use App\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class NotificationStatus extends BaseEntity
{
    public function __construct(
        private bool $isRead,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            isRead: $model->is_read,
        );
    }

    public function isUnread(): bool
    {
        return ! $this->isRead;
    }
}
