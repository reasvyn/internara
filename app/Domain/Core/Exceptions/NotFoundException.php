<?php

declare(strict_types=1);

namespace App\Domain\Core\Exceptions;

class NotFoundException extends PresentationException
{
    public function __construct(
        string $message = 'Resource not found',
        ?string $hint = null,
        array $context = [],
    ) {
        parent::__construct($message);
        $this->withHint($hint ?? 'The requested resource does not exist or has been removed.');
        $this->withContext($context);
    }
}
