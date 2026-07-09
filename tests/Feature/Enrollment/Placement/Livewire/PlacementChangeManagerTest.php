<?php

declare(strict_types=1);

use App\Enrollment\Placement\Livewire\PlacementChangeManager;
use App\Core\Livewire\BaseRecordManager;

test('extends base record manager', function () {
    expect(is_subclass_of(PlacementChangeManager::class, BaseRecordManager::class))->toBeTrue();
});

test('has headers method', function () {
    expect(method_exists(PlacementChangeManager::class, 'headers'))->toBeTrue();
});

test('has query method', function () {
    expect(method_exists(PlacementChangeManager::class, 'query'))->toBeTrue();
});
