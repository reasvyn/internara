<?php

declare(strict_types=1);

namespace App\Domain\Core\Data;

use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;

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
