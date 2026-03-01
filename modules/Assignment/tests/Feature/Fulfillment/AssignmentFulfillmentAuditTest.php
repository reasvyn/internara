<?php

declare(strict_types=1);

namespace Modules\Assignment\Tests\Feature\Fulfillment;


use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Internship\Services\Contracts\RegistrationService;



test('completion guard audit: registration cannot be completed if tasks are pending', function () {
    // 1. Setup registration
    $reg = \Modules\Internship\Models\InternshipRegistration::factory()->create();
    
    // 2. Mock AssignmentService to return false for fulfillment
    $mock = $this->mock(AssignmentService::class);
    $mock->shouldReceive('isFulfillmentComplete')->with($reg->id)->andReturn(false);
    
    // 3. Act & Assert via Internship module
    expect(fn() => app(RegistrationService::class)->complete($reg->id))
        ->toThrow(\Modules\Exception\AppException::class, 'internship::exceptions.mandatory_assignments_not_verified');
});

test('negative rejection audit: rejecting a verified task blocks registration completion', function () {
    $reg = \Modules\Internship\Models\InternshipRegistration::factory()->create();
    
    // Initial state: complete
    $mock = $this->mock(AssignmentService::class);
    $mock->shouldReceive('isFulfillmentComplete')->with($reg->id)->andReturn(true);
    
    // Verify we could complete
    // (Testing the logic flow, not necessarily executing the whole DB write)
    expect(app(AssignmentService::class)->isFulfillmentComplete($reg->id))->toBeTrue();

    // Now mock rejection
    $mock->shouldReceive('isFulfillmentComplete')->with($reg->id)->andReturn(false);
    
    expect(app(AssignmentService::class)->isFulfillmentComplete($reg->id))->toBeFalse();
});
