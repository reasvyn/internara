<?php

declare(strict_types=1);

namespace App\Domain\Internship\States;

class Active extends InternshipState
{
    public function label(): string
    {
        return 'Active';
    }
}
