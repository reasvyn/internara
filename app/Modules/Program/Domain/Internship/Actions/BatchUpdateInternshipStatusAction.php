<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\Internship\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\Program\Domain\Internship\Events\InternshipStatusBatchUpdated;
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
