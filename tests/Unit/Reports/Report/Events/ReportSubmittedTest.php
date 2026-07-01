<?php

declare(strict_types=1);

use App\Reports\Report\Events\ReportSubmitted;
use App\Reports\Report\Models\Report;

test('report submitted has report payload', function () {
    $report = new class extends Report {};
    $report->forceFill(['id' => 'r-1']);

    $event = new ReportSubmitted($report);

    expect($event->report->id)->toBe('r-1');
    expect($event->eventName())->toBe('report.submitted');
});
