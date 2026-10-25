<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\Attendance\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\User\Models\User;

final class VerifyAttendanceAction extends BaseCommandAction
{
    public function execute(Attendance $log, ?User $actor = null): Attendance
    {
        $actor ??= auth()->user();

        return $this->transaction(function () use ($log, $actor) {
            $log->update([
                'is_verified' => true,
                'verified_by' => $actor?->id ?? auth()->id(),
                'verified_at' => now(),
            ]);

            $mentorEntity = $log->registration?->asMentorEntity();
            $proxyMeta = ($actor !== null && $mentorEntity !== null)
                ? $mentorEntity->resolveProxyMetadata($actor, 'teacher')
                : [];

            $this->log('attendance_verified', $log, array_merge([
                'user_id' => $log->user_id,
                'date' => $log->date->toDateString(),
                'status' => $log->status->value,
            ], $proxyMeta), $actor);

            return $log;
        });
    }
}
