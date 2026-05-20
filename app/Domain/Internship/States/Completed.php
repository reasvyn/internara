<?php

declare(strict_types=1);

namespace App\Domain\Internship\States;

class Completed extends InternshipState
{
    public function label(): string
    {
        return 'Completed';
    }

    public function isTerminal(): bool
    {
        return true;
    }
}
