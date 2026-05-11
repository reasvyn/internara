<?php

declare(strict_types=1);

use App\Entities\Assignment\AssignmentRules;
use Carbon\Carbon;

it('detects mandatory assignment', function () {
    $entity = new AssignmentRules(true, null);

    expect($entity->isMandatory())->toBeTrue();
});

it('detects optional assignment', function () {
    $entity = new AssignmentRules(false, null);

    expect($entity->isMandatory())->toBeFalse();
});

it('detects overdue assignment', function () {
    $entity = new AssignmentRules(false, Carbon::yesterday());

    expect($entity->isOverdue(Carbon::now()))->toBeTrue();
});

it('detects not overdue assignment', function () {
    $entity = new AssignmentRules(false, Carbon::tomorrow());

    expect($entity->isOverdue(Carbon::now()))->toBeFalse();
});

it('is not overdue when no due date', function () {
    $entity = new AssignmentRules(false, null);

    expect($entity->isOverdue(Carbon::now()))->toBeFalse();
});
