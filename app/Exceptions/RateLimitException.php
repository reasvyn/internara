<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Core\Exceptions\InfrastructureException;

class RateLimitException extends InfrastructureException
{
    public function __construct(
        string $message = 'Too many requests',
        ?string $hint = null,
        array $context = [],
    ) {
        parent::__construct($message);
        $this->withHint($hint ?? 'Please wait before making another request.');
        $this->withContext($context);
    }
}
