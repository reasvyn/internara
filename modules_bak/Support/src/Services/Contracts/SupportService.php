<?php

declare(strict_types=1);

namespace Modules\Support\Services\Contracts;

interface SupportService
{
    public function getFaqs(): array;
    public function submitTicket(array $data): void;
    public function getDocumentation(string $topic): ?string;
}
