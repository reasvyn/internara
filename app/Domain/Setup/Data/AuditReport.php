<?php

declare(strict_types=1);

namespace App\Domain\Setup\Data;

use App\Domain\Setup\Enums\AuditCategory;
use App\Domain\Shared\Enums\AuditStatus;

final readonly class AuditReport
{
    /** @param list<AuditCheck> $checks */
    public function __construct(
        public array $checks,
    ) {}

    public function passed(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->category->isCritical() && $check->status === AuditStatus::Fail) {
                return false;
            }
        }

        return true;
    }

    /** @return list<AuditCheck> */
    public function failing(): array
    {
        return array_values(array_filter($this->checks, fn (AuditCheck $check) => $check->status === AuditStatus::Fail));
    }

    /** @return list<AuditCheck> */
    public function byCategory(AuditCategory $category): array
    {
        return array_values(array_filter($this->checks, fn (AuditCheck $check) => $check->category === $category));
    }
}
