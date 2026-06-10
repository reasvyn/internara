<?php

declare(strict_types=1);

use App\Assignment\Entities\AssignmentRules;
use Carbon\Carbon;

test('assignment rules detects mandatory', function () {
    $mandatory = new AssignmentRules(true, null);
    expect($mandatory->isMandatory())->toBeTrue();

    $optional = new AssignmentRules(false, null);
    expect($optional->isMandatory())->toBeFalse();
});

test('assignment rules detects overdue', function () {
    $now = new Carbon('2026-06-10');
    $pastDue = new AssignmentRules(true, new Carbon('2026-06-05'));
    expect($pastDue->isOverdue($now))->toBeTrue();

    $futureDue = new AssignmentRules(true, new Carbon('2026-06-15'));
    expect($futureDue->isOverdue($now))->toBeFalse();
});

test('assignment rules without due date is never overdue', function () {
    $rules = new AssignmentRules(true, null);
    expect($rules->isOverdue(new Carbon))->toBeFalse();
});
