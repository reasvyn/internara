<?php

declare(strict_types=1);

namespace Modules\Schedule\Services\Contracts;

use Modules\Schedule\Models\Schedule;

interface ScheduleService
{
    public function findById(string $id): ?Schedule;

    public function create(array $data): Schedule;

    public function update(Schedule $schedule, array $data): void;

    public function delete(Schedule $schedule): void;

    public function getCalendarEvents(string $startDate, string $endDate): array;
}
