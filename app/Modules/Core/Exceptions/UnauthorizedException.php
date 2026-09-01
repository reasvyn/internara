<?php

declare(strict_types=1);

namespace App\Modules\Core\Exceptions;

class UnauthorizedException extends PresentationException
{
    public function __construct(
        string $message = 'Unauthorized',
        ?string $hint = null,
        array $context = [],
    ) {
        parent::__construct($message);
        $this->withHint($hint ?? __('core.exceptions.unauthorized_hint'));
        $this->withContext($context);
    }

    public function statusCode(): int
    {
        return 403;
    }
}
