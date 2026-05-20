<?php

declare(strict_types=1);

namespace App\Domain\Internship\States;

class Cancelled extends InternshipState
{
    public function label(): string
    {
        return 'Cancelled';
    }

    public function isTerminal(): bool
    {
        return true;
    }
}
