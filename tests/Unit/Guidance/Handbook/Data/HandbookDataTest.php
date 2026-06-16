<?php

declare(strict_types=1);

use App\Guidance\Handbook\Data\HandbookData;
use App\Guidance\Handbook\Enums\HandbookAudience;

test('handbook data can be created with required fields', function () {
    $data = new HandbookData(
        title: 'PKL Guidelines',
        audience: HandbookAudience::STUDENT,
    );

    expect($data->title)->toBe('PKL Guidelines');
    expect($data->audience)->toBe(HandbookAudience::STUDENT);
    expect($data->isActive)->toBeTrue();
});

test('handbook data is immutable', function () {
    $data = new HandbookData(title: 'Test', audience: HandbookAudience::ALL);

    $r = new ReflectionClass($data);
    foreach ($r->getProperties() as $p) {
        expect($p->isReadOnly())->toBeTrue();
    }
});
