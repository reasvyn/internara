<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;

final readonly class AuditCheck
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
