<?php

declare(strict_types=1);

use App\Entities\Handbook\HandbookPublishState;
use Carbon\Carbon;

it('detects published handbook', function () {
    $entity = new HandbookPublishState(true, Carbon::now());

    expect($entity->isPublished())->toBeTrue();
});

it('detects not published when inactive', function () {
    $entity = new HandbookPublishState(false, Carbon::now());

    expect($entity->isPublished())->toBeFalse();
});

it('detects not published when no publish date', function () {
    $entity = new HandbookPublishState(true, null);

    expect($entity->isPublished())->toBeFalse();
});

it('detects not published when inactive and no date', function () {
    $entity = new HandbookPublishState(false, null);

    expect($entity->isPublished())->toBeFalse();
});
