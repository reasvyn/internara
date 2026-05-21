<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

interface SendsNotifications
{
    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): mixed;
}
