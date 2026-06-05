<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AuditStatus;
use App\Enums\AuditCategory;

final readonly class AuditReport extends BaseData
{
    /** @param AuditCheck[] $checks */
    public function __construct(
        public array $checks = [],
    ) {}

    public function passed(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->status === AuditStatus::FAIL) {
                return false;
            }
        }

        return true;
    }

    /** @return AuditCheck[] */
    public function forCategory(AuditCategory $category): array
    {
        return array_values(
            array_filter(
                $this->checks,
                fn (AuditCheck $check) => $check->category === $category,
            ),
        );
    }
}
