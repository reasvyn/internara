<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Actions;

use App\Core\Actions\BaseReadAction;
use Carbon\Carbon;

final class ReadRegistrationAvailabilityAction extends BaseReadAction
{
    public function execute(): array
    {
        $startDate = setting('registration_period_start');
        $endDate = setting('registration_period_end');

        if ($startDate === null || $endDate === null) {
            return [
                'status' => 'not_configured',
            ];
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $now = Carbon::now();
        $oneMonthFromNow = (new Carbon)->addMonth();

        if ($now->between($start, $end)) {
            return [
                'status' => 'open',
                'start_date' => $start,
                'end_date' => $end,
            ];
        }

        if ($start->between($now, $oneMonthFromNow)) {
            return [
                'status' => 'upcoming',
                'start_date' => $start,
                'end_date' => $end,
            ];
        }

        return [
            'status' => 'closed',
            'start_date' => $start,
            'end_date' => $end,
        ];
    }
}
