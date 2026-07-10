<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Livewire\SupervisorReviewManager;
use App\Core\Livewire\BaseRecordManager;

test('extends base record manager', function () {
    expect(is_subclass_of(SupervisorReviewManager::class, BaseRecordManager::class))->toBeTrue();
});

test('has headers method', function () {
    expect(method_exists(SupervisorReviewManager::class, 'headers'))->toBeTrue();
});

test('has query method', function () {
    expect(method_exists(SupervisorReviewManager::class, 'query'))->toBeTrue();
});
