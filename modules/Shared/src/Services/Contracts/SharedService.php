<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Contracts;

interface SharedService
{
    public function maskSensitiveData(string $value): string;
    public function formatDate(string $date, string $format = 'Y-m-d'): string;
    public function generateUuid(): string;
}
