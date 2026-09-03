<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Attendance\Actions;

use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Working days a student neither clocked in on nor filed an absence request for.
 *
 * Every internship working day must be accounted for: the student either attends
 * (an attendance row) or declares why they were away (an absence request). Days
 * with neither are what this action returns, so the portal can require the
 * student to file the missing request.
 */
final class ReadUnaccountedDatesAction extends BaseReadAction
{
    /** Weekend days are not internship working days. */
    private const NON_WORKING_DAYS = [CarbonImmutable::SATURDAY, CarbonImmutable::SUNDAY];

    /** Only the recent past is chased; older gaps are an administrative matter. */
    private const MAX_LOOKBACK_DAYS = 30;

    /**
     * @return Collection<int, CarbonImmutable> Oldest first
     */
    public function execute(Registration $registration, ?CarbonImmutable $today = null): Collection
    {
        $today = $today ?? CarbonImmutable::today();

        $start = $registration->start_date
            ? CarbonImmutable::parse($registration->start_date)->startOfDay()
            : $today;

        $end = $registration->end_date
            ? CarbonImmutable::parse($registration->end_date)->startOfDay()
            : $today;

        // Today is still in progress — only closed days can be overdue.
        $end = $end->greaterThan($today) ? $today->subDay() : $end;
        $start = $start->lessThan($end->subDays(self::MAX_LOOKBACK_DAYS))
            ? $end->subDays(self::MAX_LOOKBACK_DAYS)
            : $start;

        if ($start->greaterThan($end)) {
            return collect();
        }

        $accounted = Attendance::query()
            ->where('registration_id', $registration->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
            ->flip();

        $dates = collect();

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            if (in_array($day->dayOfWeek, self::NON_WORKING_DAYS, true)) {
                continue;
            }

            if ($accounted->has($day->toDateString())) {
                continue;
            }

            $dates->push($day);
        }

        return $dates;
    }
}
