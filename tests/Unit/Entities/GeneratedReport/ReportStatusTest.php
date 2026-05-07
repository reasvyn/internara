<?php

declare(strict_types=1);

use App\Entities\GeneratedReport\ReportStatus;

it('detects completed report', function () {
    $entity = new ReportStatus('completed');

    expect($entity->isCompleted())->toBeTrue()
        ->and($entity->isFailed())->toBeFalse();
});

it('detects failed report', function () {
    $entity = new ReportStatus('failed');

    expect($entity->isFailed())->toBeTrue()
        ->and($entity->isCompleted())->toBeFalse();
});

it('detects neither status', function () {
    $entity = new ReportStatus('processing');

    expect($entity->isCompleted())->toBeFalse()
        ->and($entity->isFailed())->toBeFalse();
});
