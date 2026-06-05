<?php

declare(strict_types=1);

namespace App\Program\Internship\Actions;

use App\Core\Actions\BaseAction;
use App\Program\Internship\Enums\InternshipStatus;
use Illuminate\Database\Eloquent\Builder;

final class BatchUpdateInternshipStatusAction extends BaseAction
{
    public function execute(Builder $query, InternshipStatus $status): int
    {
        return $query->update(['status' => $status->value]);
    }
}
