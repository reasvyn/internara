<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AuditStatus;
use App\Enums\AuditCategory;

final readonly class AuditCheck extends BaseData
{
    public function __construct(
        public AuditCategory $category,
        public string $nameKey,
        public AuditStatus $status,
        public string $messageKey,
        public array $nameParams = [],
        public array $messageParams = [],
    ) {}
}
