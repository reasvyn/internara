<?php

declare(strict_types=1);

use App\Reports\Report\Events\GradeCalculated;
use App\Reports\Report\Events\ReportApproved;
use App\Reports\Report\Events\ReportFinalized;
use App\Reports\Report\Models\Report;

function makeReport(string $id): Report
{
    $model = new class extends Report {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('grade calculated event name and payload', function () {
    $event = new GradeCalculated(makeReport('r-1'));

    expect($event->report->id)->toBe('r-1');
    expect($event->eventName())->toBe('report.grade_calculated');
    expect($event->toPayload())->toHaveKey('report_id');
});

test('report approved event name and payload', function () {
    $event = new ReportApproved(makeReport('r-2'));

    expect($event->report->id)->toBe('r-2');
    expect($event->eventName())->toBe('report.approved');
    expect($event->toPayload())->toHaveKey('report_id');
});

test('report finalized event name and payload', function () {
    $event = new ReportFinalized(makeReport('r-3'));

    expect($event->report->id)->toBe('r-3');
    expect($event->eventName())->toBe('report.finalized');
    expect($event->toPayload())->toHaveKey('report_id');
});
