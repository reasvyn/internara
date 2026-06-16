<?php

declare(strict_types=1);

namespace App\Program\Internship\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Events\InternshipStatusBatchUpdated;
use Illuminate\Database\Eloquent\Builder;

final class BatchUpdateInternshipStatusAction extends BaseCommandAction
{
    public function execute(Builder $query, InternshipStatus $status): int
    {
        return $this->transaction(function () use ($query, $status) {
            $count = $query->update(['status' => $status->value]);

            $this->log('internship_status_batch_updated', null, [
                'count' => $count,
                'new_status' => $status->value,
            ]);

            event(new InternshipStatusBatchUpdated(
                count: $count,
                newStatus: $status->value,
            ));

            return $count;
        });
    }
}
