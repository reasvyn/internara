<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;

final readonly class AuditReport
{
    /** @param AuditCheck[] $checks */
    public function __construct(
        public array $checks = [],
    ) {}

    public function passed(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->status === AuditStatus::Fail) {
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
