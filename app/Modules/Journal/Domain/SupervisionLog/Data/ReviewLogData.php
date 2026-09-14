<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\SupervisionLog\Data;

use App\Modules\Core\Data\BaseData;

final readonly class ReviewLogData extends BaseData
{
    public function __construct(
        public string $logId,
        public string $supervisorId,
        public string $feedback,
    ) {}
}
