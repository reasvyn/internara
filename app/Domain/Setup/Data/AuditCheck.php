<?php

declare(strict_types=1);

namespace App\Domain\Setup\Data;

use App\Domain\Setup\Enums\AuditCategory;
use App\Domain\Shared\Enums\AuditStatus;

final readonly class AuditCheck
{
    /**
     * @param array<string, mixed> $nameParams
     * @param array<string, mixed> $messageParams
     */
    public function __construct(
        public AuditCategory $category,
        public string $nameKey,
        public AuditStatus $status,
        public string $messageKey,
        public array $nameParams = [],
        public array $messageParams = [],
    ) {}

    public function name(): string
    {
        return __("setup.checks.{$this->nameKey}", $this->nameParams);
    }

    public function message(): string
    {
        return __("setup.checks.{$this->messageKey}", $this->messageParams);
    }
}
