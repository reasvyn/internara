<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Core\Exceptions\ActionException;

class ValidationFailedException extends ActionException
{
    public function __construct(
        string $message = 'Validation failed',
        ?string $hint = null,
        array $context = [],
    ) {
        parent::__construct($message);
        $this->withHint($hint ?? 'Please check your input and try again.');
        $this->withContext($context);
    }
}
