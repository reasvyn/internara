<?php

declare(strict_types=1);

use App\Entities\AcademicYear\AcademicYearState;

it('detects active academic year', function () {
    $entity = new AcademicYearState(true);

    expect($entity->isActive())->toBeTrue();
});

it('detects inactive academic year', function () {
    $entity = new AcademicYearState(false);

    expect($entity->isActive())->toBeFalse();
});
