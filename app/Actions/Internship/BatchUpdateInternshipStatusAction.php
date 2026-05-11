<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Enums\Internship\InternshipStatus;
use Illuminate\Database\Eloquent\Builder;

class BatchUpdateInternshipStatusAction
{
    public function execute(Builder $query, InternshipStatus $status): int
    {
        return $query->update(['status' => $status->value]);
    }
}
