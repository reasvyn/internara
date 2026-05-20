<?php

declare(strict_types=1);

namespace App\Domain\Internship\States;

class Published extends InternshipState
{
    public function label(): string
    {
        return 'Published';
    }
}
