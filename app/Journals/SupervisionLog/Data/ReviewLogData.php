<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Data;

use App\Core\Data\BaseData;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;

final readonly class ReviewLogData extends BaseData
{
    public function __construct(
        public SupervisionLog $log,
        public User $supervisor,
        public string $feedback,
    ) {}
}
