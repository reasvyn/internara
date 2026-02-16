<?php

declare(strict_types=1);

namespace Modules\Guidance\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guidance\Models\Handbook;
use Modules\Guidance\Services\Contracts\HandbookService;


test('it can record student acknowledgement', function () {
    $service = app(HandbookService::class);
    $handbook = Handbook::factory()->create();
    $studentId = (string) \Illuminate\Support\Str::uuid();

    $result = $service->acknowledge($studentId, $handbook->id);

    expect($result)->toBeTrue();
    expect($service->hasAcknowledged($studentId, $handbook->id))->toBeTrue();
});

test('it can check mandatory completion', function () {
    $service = app(HandbookService::class);
    $studentId = (string) \Illuminate\Support\Str::uuid();

    // Create 1 mandatory and 1 optional
    $mandatory = Handbook::factory()->create(['is_mandatory' => true]);
    $optional = Handbook::factory()->create(['is_mandatory' => false]);

    // Initially incomplete
    expect($service->hasCompletedMandatory($studentId))->toBeFalse();

    // Acknowledge mandatory
    $service->acknowledge($studentId, $mandatory->id);

    // Now complete
    expect($service->hasCompletedMandatory($studentId))->toBeTrue();
});
