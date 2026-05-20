<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Enums\InternshipStatus;
use Illuminate\Database\Eloquent\Builder;

class BatchUpdateInternshipStatusAction extends BaseAction
{
    public function execute(Builder $query, InternshipStatus $status): int
    {
        return $query->update(['status' => $status->value]);
    }
}
