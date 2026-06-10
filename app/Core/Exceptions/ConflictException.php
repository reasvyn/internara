<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class ConflictException extends ActionException
{
    public function __construct(
        string $message = 'Conflict',
        ?string $hint = null,
        array $context = [],
    ) {
        parent::__construct($message);
        $this->withHint($hint ?? 'The request conflicts with the current state of the resource.');
        $this->withContext($context);
    }

    public function statusCode(): int
    {
        return 409;
    }
}
